import React from "react";
import { Window, DraggableHandle, ControlButtons } from "@/components/Window";
import { useWasmLoader } from "./useWasmLoader";
import type { WasmLoaderProps } from "./types";

/**
 * WasmApp - Reusable WASM Module Loader Template
 *
 * A generic component for loading and displaying WASM modules.
 * Shows all exported functions and their results.
 *
 * Usage:
 * <WasmApp moduleName="my-module" />
 */

function WasmApp({ moduleName = "demo-app", wasmUrl, onLoad, onError }: WasmLoaderProps) {
  const { exports, loading, error } = useWasmLoader({
    moduleName,
    wasmUrl,
    onLoad,
    onError,
  });

  return (
    <Window
      x='center'
      y='center'
      width='875'
      height='78%'
      maxWidth='1020'
      maxHeight='90%'
    >
      <DraggableHandle className='flex items-center justify-between px-2 py-5 border-b bg-gradient-to-r from-purple-600 to-indigo-600 z-50 pl-20'>
        <span>WASM: {moduleName}</span>
      </DraggableHandle>
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
            {exports.add && typeof exports.add === "function" && (
              <div className='bg-slate-700 rounded p-4 border border-slate-600'>
                <p className='text-cyan-400 font-mono text-sm mb-2'>add(5, 3)</p>
                <p className='text-slate-200 text-sm'>
                  Result: {(exports.add as any)(5, 3)}
                </p>
              </div>
            )}

            {/* Demo: Multiply function */}
            {exports.multiply && typeof exports.multiply === "function" && (
              <div className='bg-slate-700 rounded p-4 border border-slate-600'>
                <p className='text-cyan-400 font-mono text-sm mb-2'>multiply(4, 7)</p>
                <p className='text-slate-200 text-sm'>
                  Result: {(exports.multiply as any)(4, 7)}
                </p>
              </div>
            )}

            {/* Demo: Greet function */}
            {exports.greet && typeof exports.greet === "function" && (
              <div className='bg-slate-700 rounded p-4 border border-slate-600'>
                <p className='text-cyan-400 font-mono text-sm mb-2'>greet('World')</p>
                <p className='text-slate-200 text-sm'>
                  {(exports.greet as any)("World")}
                </p>
              </div>
            )}

            {/* Demo: Fibonacci function */}
            {exports.fibonacci && typeof exports.fibonacci === "function" && (
              <div className='bg-slate-700 rounded p-4 border border-slate-600'>
                <p className='text-cyan-400 font-mono text-sm mb-2'>fibonacci(10)</p>
                <p className='text-slate-200 text-sm'>
                  Result: {(exports.fibonacci as any)(10)}
                </p>
              </div>
            )}

            {/* Demo: Process data function */}
            {exports.process_data && typeof exports.process_data === "function" && (
              <div className='bg-slate-700 rounded p-4 border border-slate-600'>
                <p className='text-cyan-400 font-mono text-sm mb-2'>
                  process_data('hello')
                </p>
                <p className='text-slate-200 text-sm'>
                  {(exports.process_data as any)("hello")}
                </p>
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
