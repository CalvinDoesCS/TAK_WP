/**
 * Admin Dashboard Page
 * Loads Octarine Wasm module
 */

import { MicroAppContainer } from '@/components/MicroAppContainer'
import type { Metadata } from 'next'

export const metadata: Metadata = {
  title: 'Admin Dashboard | DaaS Platform',
  description: 'Administrative dashboard powered by WebAssembly',
}

export default function AdminDashboard() {
  return (
    <main className="min-h-screen bg-gray-50">
      <MicroAppContainer 
        appId="octarine" 
        route="/admin"
      />
    </main>
  )
}
