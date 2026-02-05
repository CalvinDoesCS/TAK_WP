export interface WasmExports {
  [key: string]: any;
  memory?: WebAssembly.Memory;
}

export interface WasmLoaderProps {
  moduleName: string;
  wasmUrl?: string;
  jsUrl?: string;
  onLoad?: (exports: WasmExports) => void;
  onError?: (error: string) => void;
}
