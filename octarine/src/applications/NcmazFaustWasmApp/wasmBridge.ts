/**
 * Lightweight WASM bridge for nacmaz_faust
 * Provides clear, promise-friendly helper methods around the raw wasm exports.
 * The bridge does not auto-load the wasm; pass the `wasm` exports object
 * (returned from `useWasmLoader`) into `createWasmBridge`.
 */

type WasmExports = any;

function readBytes(memory: WebAssembly.Memory, ptr: number, len: number) {
  return new Uint8Array(memory.buffer, ptr, len);
}

function readString(memory: WebAssembly.Memory, ptr: number, len: number) {
  return new TextDecoder().decode(readBytes(memory, ptr, len));
}

export function createWasmBridge(wasm: WasmExports) {
  if (!wasm) {
    return createNoopBridge();
  }

  const urlCache = new Map<string, string>();

  function ensureReady() {
    if (!wasm?.memory) throw new Error("WASM memory not available");
  }

  function listFiles(): string[] {
    ensureReady();
    if (!wasm.get_file_count || !wasm.get_file_path_ptr || !wasm.get_file_path_len) {
      return [];
    }
    const memory = wasm.memory as WebAssembly.Memory;
    const count = Number(wasm.get_file_count());
    const out: string[] = [];
    for (let i = 0; i < count; i++) {
      const ptr = Number(wasm.get_file_path_ptr(i));
      const len = Number(wasm.get_file_path_len(i));
      if (!ptr || !len) continue;
      out.push(readString(memory, ptr, len));
    }
    return out;
  }

  function getFile(path: string): { bytes: Uint8Array; mime: string | null } | null {
    ensureReady();
    if (
      !wasm.alloc ||
      !wasm.dealloc ||
      !wasm.get_file ||
      !wasm.get_last_file_ptr ||
      !wasm.get_last_file_len
    ) {
      return null;
    }

    const memory = wasm.memory as WebAssembly.Memory;
    const encoder = new TextEncoder();
    const normalized = path.startsWith("/") ? path : `/${path}`;
    const pathBytes = encoder.encode(normalized);
    const ptr = Number(wasm.alloc(pathBytes.length));
    try {
      readBytes(memory, ptr, pathBytes.length).set(pathBytes);
      const ok = Number(wasm.get_file(ptr, pathBytes.length));
      if (!ok) return null;

      const filePtr = Number(wasm.get_last_file_ptr());
      const fileLen = Number(wasm.get_last_file_len());
      const mimePtr = wasm.get_last_file_mime_ptr ? Number(wasm.get_last_file_mime_ptr()) : 0;
      const mimeLen = wasm.get_last_file_mime_len ? Number(wasm.get_last_file_mime_len()) : 0;

      const bytes = readBytes(memory, filePtr, fileLen).slice();
      const mime = mimePtr && mimeLen ? readString(memory, mimePtr, mimeLen) : null;
      return { bytes, mime };
    } finally {
      try {
        wasm.dealloc(ptr, pathBytes.length);
      } catch {
        // ignore
      }
    }
  }

  function getFileUrl(path: string): string | null {
    const rec = getFile(path);
    if (!rec) return null;
    const key = path;
    if (urlCache.has(key)) return urlCache.get(key)!;
      const arr = rec.bytes as Uint8Array;

      // Create a tightly-sliced ArrayBuffer for the exact bytes (avoid SharedArrayBuffer typing issues)
    const ab = arr.buffer.slice(arr.byteOffset, arr.byteOffset + arr.byteLength) as unknown as ArrayBuffer;
    const blob = new Blob([ab], { type: rec.mime ?? "application/octet-stream" });
    const url = URL.createObjectURL(blob);
    urlCache.set(key, url);
    return url;
  }

  function revokeFileUrl(path: string) {
    const url = urlCache.get(path);
    if (!url) return;
    URL.revokeObjectURL(url);
    urlCache.delete(path);
  }

  async function injectWpData(data: object | string): Promise<boolean> {
    ensureReady();
    if (!wasm.receive_wp_data || !wasm.alloc || !wasm.dealloc) {
      throw new Error("WASM does not support receive_wp_data");
    }
    const payload = typeof data === "string" ? data : JSON.stringify(data);
    const encoder = new TextEncoder();
    const bytes = encoder.encode(payload);
    const ptr = Number(wasm.alloc(bytes.length));
    try {
      readBytes(wasm.memory as WebAssembly.Memory, ptr, bytes.length).set(bytes);
      const ok = Number(wasm.receive_wp_data(ptr, bytes.length));
      return Boolean(ok);
    } finally {
      try {
        wasm.dealloc(ptr, bytes.length);
      } catch {
        // ignore
        console.warn("Failed to dealloc WP data in WASM");
      }
    }
  }

  function freeAllUrls() {
    for (const url of urlCache.values()) URL.revokeObjectURL(url);
    urlCache.clear();
  }

  return {
    isReady: () => Boolean(wasm && wasm.memory),
    listFiles,
    getFile,
    getFileUrl,
    revokeFileUrl,
    injectWpData,
    freeAllUrls,
  } as const;
}

function createNoopBridge() {
  return {
    isReady: () => false,
    listFiles: () => [] as string[],
    getFile: (_: string) => null as null,
    getFileUrl: (_: string) => null as null,
    revokeFileUrl: (_: string) => undefined,
    injectWpData: async (_: any) => {
      throw new Error("WASM not loaded");
    },
    freeAllUrls: () => undefined,
  };
}

export type WasmBridge = ReturnType<typeof createWasmBridge>;

export default createWasmBridge;
