/**
 * React Hook for WebAssembly Modules
 */

import { useState, useEffect, useCallback } from 'react'
import { wasmLoader, WasmModule } from '../lib/wasmLoader'

interface UseWasmModuleOptions {
  id: string
  name: string
  version: string
  url: string
  autoLoad?: boolean
}

interface UseWasmModuleResult {
  module: WasmModule | null
  loading: boolean
  error: Error | null
  execute: (functionName: string, ...args: any[]) => Promise<any>
  reload: () => Promise<void>
}

export function useWasmModule(options: UseWasmModuleOptions): UseWasmModuleResult {
  const [module, setModule] = useState<WasmModule | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<Error | null>(null)

  const loadModule = useCallback(async () => {
    setLoading(true)
    setError(null)

    try {
      const loadedModule = await wasmLoader.loadModule({
        id: options.id,
        name: options.name,
        version: options.version,
        url: options.url
      })
      
      setModule(loadedModule)
    } catch (err) {
      setError(err as Error)
      console.error('Failed to load Wasm module:', err)
    } finally {
      setLoading(false)
    }
  }, [options.id, options.name, options.version, options.url])

  useEffect(() => {
    if (options.autoLoad !== false) {
      loadModule()
    }

    return () => {
      // Cleanup on unmount if needed
      // wasmLoader.unloadModule(options.id)
    }
  }, [loadModule, options.autoLoad, options.id])

  const execute = useCallback(async (functionName: string, ...args: any[]) => {
    if (!module) {
      throw new Error('Module not loaded')
    }

    return wasmLoader.executeFunction(options.id, functionName, ...args)
  }, [module, options.id])

  const reload = useCallback(async () => {
    wasmLoader.unloadModule(options.id)
    await loadModule()
  }, [options.id, loadModule])

  return { module, loading, error, execute, reload }
}
