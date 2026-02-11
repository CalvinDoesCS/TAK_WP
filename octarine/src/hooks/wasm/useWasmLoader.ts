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

        const wpApiBase = import.meta.env.VITE_WP_API_BASE ?? "http://localhost:8080";
        const addCacheBuster = (input?: string) => {
          if (!input) return input;
          const separator = input.includes("?") ? "&" : "?";
          return `${input}${separator}t=${Date.now()}`;
        };

        // Construct WASM URL if not provided
        let url = props.wasmUrl;
        if (!url) {
          url = `${wpApiBase}/wp-json/tak/v1/wasm/${props.moduleName}`;
        }

        if (props.jsUrl) {
          const jsUrl = addCacheBuster(props.jsUrl);
          const wasmUrl = addCacheBuster(url);

          console.log(`📦 Loading WASM JS glue from: ${jsUrl}`);
          if (!jsUrl) {
            throw new Error("Missing jsUrl for WASM JS glue");
          }

          const jsModule = await import(/* @vite-ignore */ jsUrl);
          const initWasm = jsModule?.default ?? jsModule?.init ?? jsModule?.__wbg_init;

          if (typeof initWasm !== "function") {
            throw new Error("WASM JS glue does not export an init function");
          }

          const wasmExports = (await initWasm({ module_or_path: wasmUrl })) as WasmExports;
          console.log("🎉 WASM module initialized via JS glue");

          const memory = wasmExports.memory as WebAssembly.Memory | undefined;
          const wrappedExports = { ...wasmExports };

          if (memory && typeof wasmExports.get_state_json === "function") {
            wrappedExports.get_state_json = (roomId: number) => {
              const ptr = (wasmExports.get_state_json as Function)(roomId);
              return readStringFromMemory(memory, ptr);
            };
          }

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

          if (props.onLoad) {
            props.onLoad(wrappedExports);
          }

          return;
        }

        const wasmUrl = addCacheBuster(url);

        console.log(`📦 Loading WASM module from: ${wasmUrl}`);

        if (!wasmUrl) {
          throw new Error("Missing wasmUrl for WASM module");
        }

        // Fetch WASM module
        const response = await fetch(wasmUrl);
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
  }, [props.moduleName, props.wasmUrl, props.jsUrl]);

  return { exports, loading, error };
}
