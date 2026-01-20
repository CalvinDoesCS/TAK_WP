/**
 * Micro-Frontend Registry
 * Manages available Wasm micro-apps and their metadata
 */

export interface MicroApp {
  id: string
  name: string
  description: string
  version: string
  wasmUrl: string
  routes: string[]
  permissions: string[]
  icon?: string
}

export const MICRO_APPS: Record<string, MicroApp> = {
  octarine: {
    id: 'octarine',
    name: 'Admin Dashboard',
    description: 'System administration and analytics',
    version: '1.0.0',
    wasmUrl: '/wasm/octarine.wasm',
    routes: ['/admin', '/dashboard', '/analytics'],
    permissions: ['admin', 'superuser'],
    icon: '📊'
  },
  
  opencore: {
    id: 'opencore',
    name: 'SaaS Platform',
    description: 'Customer portal and billing',
    version: '5.0.1',
    wasmUrl: '/wasm/opencore.wasm',
    routes: ['/app', '/billing', '/settings'],
    permissions: ['user', 'admin'],
    icon: '🚀'
  },
  
  wplms: {
    id: 'wplms',
    name: 'Learning Management',
    description: 'Courses and training',
    version: '4.9.0',
    wasmUrl: '/wasm/wplms.wasm',
    routes: ['/courses', '/learn', '/training'],
    permissions: ['student', 'instructor', 'admin'],
    icon: '📚'
  }
}

export function getMicroAppByRoute(path: string): MicroApp | undefined {
  return Object.values(MICRO_APPS).find(app => 
    app.routes.some(route => path.startsWith(route))
  )
}

export function canAccessMicroApp(appId: string, userPermissions: string[]): boolean {
  const app = MICRO_APPS[appId]
  if (!app) return false
  
  return app.permissions.some(permission => 
    userPermissions.includes(permission)
  )
}
