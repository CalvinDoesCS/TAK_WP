/**
 * SaaS Platform Page
 * Loads Open Core Wasm module
 */

import { MicroAppContainer } from '@/components/MicroAppContainer'
import type { Metadata } from 'next'

export const metadata: Metadata = {
  title: 'SaaS Platform | DaaS',
  description: 'Desktop as a Service platform powered by WebAssembly',
}

export default function SaaSPlatform() {
  return (
    <main className="min-h-screen bg-gray-50">
      <MicroAppContainer 
        appId="opencore" 
        route="/app"
      />
    </main>
  )
}
