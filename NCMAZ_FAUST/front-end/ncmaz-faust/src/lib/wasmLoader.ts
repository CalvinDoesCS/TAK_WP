/**
 * WebAssembly Module Loader for Micro-Frontends
 * Dynamically loads and manages Wasm micro-apps
 */

export interface WasmModule {
  id: string
  name: string
  version: string
  url: string
  instance?: WebAssembly.Instance
  exports?: any
}

export class WasmModuleLoader {
  private modules: Map<string, WasmModule> = new Map()
  private cache: Map<string, ArrayBuffer> = new Map()

  /**
   * Load a Wasm module from URL
   */
  async loadModule(moduleConfig: Omit<WasmModule, 'instance' | 'exports'>): Promise<WasmModule> {
    const { id, url } = moduleConfig

    // Check if already loaded
    if (this.modules.has(id)) {
      return this.modules.get(id)!
    }

    try {
      // Fetch Wasm binary
      const response = await fetch(url)
      if (!response.ok) {
        throw new Error(`Failed to fetch ${url}: ${response.status} ${response.statusText}`)
      }
      const buffer = await response.arrayBuffer()
      
      // Cache for future use
      this.cache.set(id, buffer)

      // Compile and instantiate
      const module = await WebAssembly.compile(buffer)
      const instance = await WebAssembly.instantiate(module, this.getImports())

      const wasmModule: WasmModule = {
        ...moduleConfig,
        instance,
        exports: instance.exports
      }

      this.modules.set(id, wasmModule)
      return wasmModule
    } catch (error) {
      console.error(`Failed to load Wasm module ${id}:`, error)
      throw error
    }
  }

  /**
   * Get module by ID
   */
  getModule(id: string): WasmModule | undefined {
    return this.modules.get(id)
  }

  /**
   * Execute function from loaded module
   */
  async executeFunction(moduleId: string, functionName: string, ...args: any[]): Promise<any> {
    const module = this.modules.get(moduleId)
    if (!module || !module.exports) {
      throw new Error(`Module ${moduleId} not loaded`)
    }

    const fn = module.exports[functionName]
    if (typeof fn !== 'function') {
      throw new Error(`Function ${functionName} not found in module ${moduleId}`)
    }

    return fn(...args)
  }

  /**
   * Unload module and free memory
   */
  unloadModule(id: string): void {
    this.modules.delete(id)
    this.cache.delete(id)
  }

  /**
   * Provide imports for Wasm modules
   * This is where we inject WordPress API, auth, etc.
   */
  private getImports(): WebAssembly.Imports {
    return {
      index: {
        // Logging (AssemblyScript console.log)
        log: (ptr: number) => {
          console.log('[Wasm Module]:', ptr)
        },

        // Authentication
        get_auth_token: () => {
          // TODO: Get JWT token from WordPress
          return 0
        }
      },
      env: {
        // Abort handler for AssemblyScript
        abort: (msg: number, file: number, line: number, col: number) => {
          console.error(`[Wasm Abort] at ${file}:${line}:${col}`)
        }
      }
    }
  }
}

// Singleton instance
export const wasmLoader = new WasmModuleLoader()
