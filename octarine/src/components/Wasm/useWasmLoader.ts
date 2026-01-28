import React from "react";
import { WasmExports, WasmLoaderProps } from "./types";

/**
 * Helper: Read null-terminated string from WASM memory
 */
function readStringFromMemory(memory: WebAssembly.Memory, ptr: number): string {
  const buffer = new Uint8Array(memory.buffer, ptr, 4096); // Max 4KB read
  let len = 0;
  while (buffer[len] !== 0 && len < buffer.length) {
    len++;
  }
  return new TextDecoder().decode(buffer.slice(0, len));
}

/**
 * WasmLoader Hook
 * Handles loading and instantiation of WASM modules
 * Returns exports and loading state
 */
export function useWasmLoader(props: WasmLoaderProps) {
  const [exports, setExports] = React.useState<WasmExports | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    const loadWasm = async () => {
      try {
        setLoading(true);
        setError(null);

        // Construct URL if not provided
        let url = props.wasmUrl;
        if (!url) {
          const wpApiBase = (import.meta as any)?.env?.VITE_WP_API_BASE ?? "";
          url = `${wpApiBase}/wp-json/octane/v1/wasm/${props.moduleName}?t=${Date.now()}`;
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

        // Wrap string-returning functions to read from memory
        const memory = wasmExports.memory as WebAssembly.Memory;
        const wrappedExports = { ...wasmExports };

        if (typeof wasmExports.get_state_json === "function") {
          wrappedExports.get_state_json = (roomId: number) => {
            const ptr = (wasmExports.get_state_json as Function)(roomId);
            return readStringFromMemory(memory, ptr);
          };
        }

        // Call init if available (can be disabled with skipInit prop)
        const skipInit = (props as any).skipInit === true;
        if (!skipInit) {
          if (typeof wasmExports.init_app === "function") {
            console.log("🔧 Calling init_app()");
            wasmExports.init_app();
          } else if (typeof wasmExports.init_room === "function") {
            console.log("🔧 Calling init_room()");
            wasmExports.init_room();
          }
        }

        setExports(wrappedExports);

        // Callback
        if (props.onLoad) {
          props.onLoad(wrappedExports);
        }
      } catch (err) {
        const message = err instanceof Error ? err.message : String(err);
        console.error("❌ WASM load error:", message);
        setError(message);

        if (props.onError) {
          props.onError(message);
        }
      } finally {
        setLoading(false);
      }
    };

    loadWasm();
  }, [props.moduleName, props.wasmUrl]);

  return { exports, loading, error };
}
