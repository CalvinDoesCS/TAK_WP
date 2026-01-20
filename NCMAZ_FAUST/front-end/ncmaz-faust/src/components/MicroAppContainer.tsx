/**
 * MicroApp Container Component
 * Dynamically loads and renders WebAssembly micro-frontends
 */

'use client'

import React, { useEffect, useState } from 'react'
import { useWasmModule } from '../hooks/useWasmModule'
import { getMicroAppByRoute, MicroApp } from '../lib/microAppRegistry'

interface MicroAppContainerProps {
  appId: string
  route: string
}

export const MicroAppContainer: React.FC<MicroAppContainerProps> = ({ appId, route }) => {
  const [appData, setAppData] = useState<any>(null)
  const [componentHtml, setComponentHtml] = useState<string>('')
  const [debugInfo, setDebugInfo] = useState<string>('')

  // Get app config from registry
  const appConfig = getMicroAppByRoute(route)

  // Load the Wasm module
  const { module, loading, error, execute } = useWasmModule({
    id: appId,
    name: appConfig?.name || appId,
    version: appConfig?.version || '1.0.0',
    url: appConfig?.wasmUrl || `/wasm/${appId}.wasm`,
    autoLoad: true
  })

  // Helper to read string from Wasm memory
  const readString = (ptr: number): string => {
    if (!module?.instance?.exports.memory) return ''
    
    try {
      const memory = module.instance.exports.memory as WebAssembly.Memory
      const buffer = new Uint8Array(memory.buffer)
      
      // AssemblyScript string layout: [length (4 bytes)] [utf16 chars...]
      const lengthBytes = buffer.slice(ptr - 4, ptr)
      const length = new Uint32Array(lengthBytes.buffer)[0]
      
      // Read UTF-16 encoded string with proper unicode handling
      const stringBytes = buffer.slice(ptr, ptr + length)
      const utf16 = new Uint16Array(stringBytes.buffer, stringBytes.byteOffset, length / 2)
      
      // Use TextDecoder with fatal mode disabled to handle invalid surrogates gracefully
      const decoder = new TextDecoder('utf-16le', { fatal: false, ignoreBOM: true })
      return decoder.decode(new Uint8Array(utf16.buffer, utf16.byteOffset, utf16.byteLength))
    } catch (err) {
      console.error('Failed to read string:', err)
      return ''
    }
  }

  useEffect(() => {
    if (module && execute) {
      // Initialize the module
      execute('init').catch(console.error)

      // Render the component
      renderComponent()
    }
  }, [module, execute])

  const renderComponent = async () => {
    try {
      console.log(`🎯 Rendering ${appId}...`)
      
      // Different apps may expose different render functions
      switch (appId) {
        case 'octarine':
          const statsPtr = await execute('getDashboardStats')
          const statsJson = readString(statsPtr as number)
          console.log('📊 Octarine stats:', statsJson)
          setAppData(JSON.parse(statsJson))
          setDebugInfo(`Loaded at ${new Date().toLocaleTimeString()}`)
          break
          
        case 'opencore':
          const metricsPtr = await execute('getMetrics')
          const metricsJson = readString(metricsPtr as number)
          console.log('📈 OpenCore metrics:', metricsJson)
          setAppData(JSON.parse(metricsJson))
          setDebugInfo(`Loaded at ${new Date().toLocaleTimeString()}`)
          break
          
        default:
          // Generic render
          const dataPtr = await execute('renderComponent', 1)
          const html = readString(dataPtr as number)
          setComponentHtml(html)
      }
    } catch (err) {
      console.error('❌ Failed to render component:', err)
      setDebugInfo(`Error: ${err instanceof Error ? err.message : String(err)}`)
    }
  }

  if (loading) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="text-center">
          <div className="w-12 h-12 mx-auto mb-4 border-b-2 border-blue-500 rounded-full animate-spin"></div>
          <p className="text-gray-600">Loading {appConfig?.name || 'micro-app'}...</p>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="flex items-center justify-center min-h-screen">
        <div className="max-w-md p-6 border border-red-200 rounded-lg bg-red-50">
          <h3 className="mb-2 font-semibold text-red-800">Failed to Load Module</h3>
          <p className="text-sm text-red-600">{error.message}</p>
          <button 
            onClick={() => window.location.reload()}
            className="px-4 py-2 mt-4 text-white bg-red-600 rounded hover:bg-red-700"
          >
            Retry
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="micro-app-container">
      {debugInfo && (
        <div className="px-4 py-2 mb-4 text-xs text-gray-600 bg-gray-100 rounded">
          🔧 Debug: {debugInfo}
        </div>
      )}
      <MicroAppRenderer 
        appId={appId}
        appConfig={appConfig}
        data={appData}
        html={componentHtml}
        onAction={(action, data) => execute('handleAction', action, data)}
      />
    </div>
  )
}

interface MicroAppRendererProps {
  appId: string
  appConfig: MicroApp | undefined
  data: any
  html: string
  onAction: (action: string, data: string) => Promise<any>
}

const MicroAppRenderer: React.FC<MicroAppRendererProps> = ({ 
  appId, 
  appConfig, 
  data,
  html,
  onAction 
}) => {
  switch (appId) {
    case 'octarine':
      return <OctarineDashboard data={data} onAction={onAction} />
      
    case 'opencore':
      return <OpenCorePlatform data={data} onAction={onAction} />
      
    default:
      return (
        <div className="p-8">
          <h1 className="mb-4 text-2xl font-bold">{appConfig?.name}</h1>
          <div dangerouslySetInnerHTML={{ __html: html }} />
        </div>
      )
  }
}

// Octarine Dashboard UI - Full VM-like experience
const OctarineDashboard: React.FC<{ data: any; onAction: any }> = ({ data }) => {
  return (
    <div className="relative w-full" style={{ height: 'calc(100vh - 300px)', minHeight: '600px' }}>
      {/* Stats Bar */}
      {data && (
        <div className="grid grid-cols-4 gap-4 p-4 mb-4 border-b bg-gray-50">
          <StatCard title="Users" value={data.users} icon="👥" />
          <StatCard title="Revenue" value={`$${data.revenue?.toLocaleString()}`} icon="💰" />
          <StatCard title="Desktops" value={data.activeDesktops} icon="🖥️" />
          <StatCard title="Health" value={data.systemHealth} icon="✅" />
        </div>
      )}
      
      {/* Full App in iframe (VM-like) */}
      <div className="relative w-full h-full overflow-hidden bg-white border border-gray-300 rounded-lg shadow-inner">
        <div className="absolute top-0 left-0 right-0 px-4 py-2 text-xs text-white bg-gray-800">
          <div className="flex items-center justify-between">
            <span>🖥️ Virtual Desktop - Octarine Admin</span>
            <span className="px-2 py-1 text-green-400 bg-gray-700 rounded">● ACTIVE</span>
          </div>
        </div>
        <iframe
          src="http://localhost:5500"
          className="w-full h-full border-0"
          style={{ marginTop: '32px', height: 'calc(100% - 32px)' }}
          title="Octarine Admin Dashboard"
          sandbox="allow-same-origin allow-scripts allow-forms allow-popups"
        />
      </div>
      
      <p className="mt-2 text-xs text-center text-gray-500">
        ⚡ Powered by WebAssembly - Full application running in virtual desktop
      </p>
    </div>
  )
}

// Open Core Platform UI - Full VM-like experience
const OpenCorePlatform: React.FC<{ data: any; onAction: any }> = ({ data }) => {
  return (
    <div className="relative w-full" style={{ height: 'calc(100vh - 300px)', minHeight: '600px' }}>
      {/* Stats Bar */}
      {data && (
        <div className="grid grid-cols-4 gap-4 p-4 mb-4 border-b bg-gray-50">
          <StatCard title="Customers" value={data.totalCustomers} icon="🏢" />
          <StatCard title="Desktops" value={data.activeDesktops} icon="💻" />
          <StatCard title="Revenue" value={`$${data.revenue?.toLocaleString()}`} icon="📈" />
          <StatCard title="Uptime" value={`${data.uptime}%`} icon="⚡" />
        </div>
      )}
      
      {/* Full App in iframe (VM-like) */}
      <div className="relative w-full h-full overflow-hidden bg-white border border-gray-300 rounded-lg shadow-inner">
        <div className="absolute top-0 left-0 right-0 px-4 py-2 text-xs text-white bg-gray-800">
          <div className="flex items-center justify-between">
            <span>🖥️ Virtual Desktop - Open Core SaaS</span>
            <span className="px-2 py-1 text-green-400 bg-gray-700 rounded">● ACTIVE</span>
          </div>
        </div>
        <iframe
          src="http://localhost:4000"
          className="w-full h-full border-0"
          style={{ marginTop: '32px', height: 'calc(100% - 32px)' }}
          title="Open Core SaaS Platform"
          sandbox="allow-same-origin allow-scripts allow-forms allow-popups"
        />
      </div>
      
      <p className="mt-2 text-xs text-center text-gray-500">
        🎯 DaaS Platform - Full SaaS application running in virtual desktop
      </p>
    </div>
  )
}

// Reusable Stat Card (compact for VM view)
const StatCard: React.FC<{ title: string; value: any; icon: string }> = ({ title, value, icon }) => {
  return (
    <div className="flex items-center p-3 bg-white border border-gray-200 rounded shadow-sm">
      <span className="mr-2 text-xl">{icon}</span>
      <div className="flex-1">
        <div className="text-xs text-gray-500">{title}</div>
        <div className="text-lg font-bold text-gray-900">{value}</div>
      </div>
    </div>
  )
}
