import React, { useState, useEffect, useRef } from "react";
import { Window, DraggableHandle, ControlButtons } from "@/components/Window";

interface WasmExports {
  greet?: (name: string) => string;
  init_app?: () => void;
  fibonacci?: (n: number) => number;
  add?: (a: number, b: number) => number;
  multiply?: (a: number, b: number) => number;
  process_data?: (data: string) => string;
  memory?: WebAssembly.Memory;
}

interface WasmAppProps {
  moduleName?: string;
  wasmUrl?: string;
  onLoad?: (exports: WasmExports) => void;
}

/**
 * WasmApp - Desktop application component
 * Loads and runs WebAssembly modules in Octarine
 *
 * Usage in Desktop:
 * {
 *   id: 'wasm-1',
 *   title: 'WASM Module',
 *   moduleName: 'tak_wasm_apps',
 *   icon: '⚙️'
 * }
 */
function WasmApp({ moduleName = "demo-room", wasmUrl, onLoad }: WasmAppProps) {
  const [exports, setExports] = useState<WasmExports | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const wasmInstanceRef = useRef<WasmExports | null>(null);

  useEffect(() => {
    const loadWasm = async () => {
      try {
        setLoading(true);
        setError(null);

        // Construct URL if not provided
        let url = wasmUrl;
        if (!url) {
          // Use Octane Core plugin REST API endpoint
          const wpApiBase = (import.meta as any)?.env?.VITE_WP_API_BASE ?? "";
          url = `${wpApiBase}/wp-json/octane/v1/wasm/${moduleName}`;
        }

        console.log(`📦 Loading WASM module from: ${url}`);

        // Fetch WASM module
        const response = await fetch(url);

        if (!response.ok) {
          throw new Error(`Failed to fetch WASM: ${response.statusText}`);
        }

        const buffer = await response.arrayBuffer();
        console.log(`✅ Downloaded ${buffer.byteLength} bytes`);

        // Instantiate WASM module
        const wasmModule = await WebAssembly.instantiate(buffer);
        const wasmExports = wasmModule.instance.exports as WasmExports;

        console.log("🎉 WASM module instantiated");

        // Call init if available
        if (wasmExports.init_app) {
          wasmExports.init_app();
        }

        // Store and set state
        wasmInstanceRef.current = wasmExports;
        setExports(wasmExports);

        // Callback
        if (onLoad) {
          onLoad(wasmExports);
        }
      } catch (err) {
        const message = err instanceof Error ? err.message : String(err);
        console.error("❌ WASM load error:", message);
        setError(message);
      } finally {
        setLoading(false);
      }
    };

    loadWasm();
  }, [moduleName, wasmUrl, onLoad]);

  return (
    <Window
      x='center'
      y='center'
      width='875'
      height='78%'
      maxWidth='1020'
      maxHeight='90%'
    >
      <DraggableHandle>WASM: {moduleName}</DraggableHandle>
      <ControlButtons />

      <div className='flex-1 overflow-auto p-4 bg-gradient-to-br from-slate-900 to-slate-800'>
        {loading && (
          <div className='flex flex-col items-center justify-center h-full gap-3'>
            <div className='text-4xl animate-spin'>⏳</div>
            <p className='text-slate-300'>Loading WASM module...</p>
          </div>
        )}

        {error && (
          <div className='bg-red-900/30 border border-red-600 rounded p-4'>
            <p className='text-red-400 font-mono text-sm'>❌ Error</p>
            <p className='text-red-300 text-xs mt-2'>{error}</p>
          </div>
        )}

        {exports && (
          <div className='space-y-4'>
            <div className='bg-green-900/20 border border-green-600 rounded p-3'>
              <p className='text-green-400 font-mono text-sm'>✅ WASM Loaded</p>
            </div>

            {/* Demo: Add function */}
            {exports.add && (
              <div className='bg-slate-700 rounded p-4 border border-slate-600'>
                <p className='text-cyan-400 font-mono text-sm mb-2'>add(5, 3)</p>
                <p className='text-slate-200 text-sm'>Result: {exports.add(5, 3)}</p>
              </div>
            )}

            {/* Demo: Multiply function */}
            {exports.multiply && (
              <div className='bg-slate-700 rounded p-4 border border-slate-600'>
                <p className='text-cyan-400 font-mono text-sm mb-2'>multiply(4, 7)</p>
                <p className='text-slate-200 text-sm'>Result: {exports.multiply(4, 7)}</p>
              </div>
            )}

            {/* Demo: Greet function */}
            {exports.greet && (
              <div className='bg-slate-700 rounded p-4 border border-slate-600'>
                <p className='text-cyan-400 font-mono text-sm mb-2'>greet('World')</p>
                <p className='text-slate-200 text-sm'>{exports.greet("World")}</p>
              </div>
            )}

            {/* Demo: Fibonacci function */}
            {exports.fibonacci && (
              <div className='bg-slate-700 rounded p-4 border border-slate-600'>
                <p className='text-cyan-400 font-mono text-sm mb-2'>fibonacci(10)</p>
                <p className='text-slate-200 text-sm'>Result: {exports.fibonacci(10)}</p>
              </div>
            )}

            {/* Demo: Process data function */}
            {exports.process_data && (
              <div className='bg-slate-700 rounded p-4 border border-slate-600'>
                <p className='text-cyan-400 font-mono text-sm mb-2'>
                  process_data('hello')
                </p>
                <p className='text-slate-200 text-sm'>{exports.process_data("hello")}</p>
              </div>
            )}

            {/* Available Functions */}
            <div className='bg-slate-700 rounded p-4 border border-slate-600'>
              <p className='text-cyan-400 font-mono text-sm mb-2'>Available Functions</p>
              <ul className='space-y-1'>
                {Object.keys(exports)
                  .filter(
                    (key) =>
                      key !== "memory" && typeof (exports as any)[key] === "function",
                  )
                  .map((key) => (
                    <li key={key} className='text-slate-300 text-xs font-mono'>
                      • {key}()
                    </li>
                  ))}
              </ul>
            </div>
          </div>
        )}
      </div>
    </Window>
  );
}

export default WasmApp;
