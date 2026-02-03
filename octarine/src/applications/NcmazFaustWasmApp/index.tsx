import React from "react";
import { Window, DraggableHandle, ControlButtons } from "@/components/Window";
import { loadNcmazFaustWasm } from "@/components/Wasm";

const DEFAULT_MODULE_NAME = "ncmaz-faust";

function NcmazFaustWasmApp() {
  const containerRef = React.useRef<HTMLDivElement | null>(null);
  const [loading, setLoading] = React.useState(true);
  const [error, setError] = React.useState<string | null>(null);

  React.useEffect(() => {
    let revoked = false;
    let revokeUrls: Array<() => void> = [];

    const mount = async () => {
      if (!containerRef.current) return;

      setLoading(true);
      setError(null);

      try {
        const wpApiBase = (import.meta as any)?.env?.VITE_WP_API_BASE ?? "";
        const wasmUrl = `${wpApiBase}/wp-json/octane/v1/wasm/${DEFAULT_MODULE_NAME}`;

        const result = await loadNcmazFaustWasm({
          wasmUrl,
          container: containerRef.current,
          wpDataProvider: async () => {
            const url = `${wpApiBase}/wp-json/octane/v1/ncmaz-data`;
            const response = await fetch(url, {
              credentials: "include",
              headers: {
                Accept: "application/json",
              },
            });

            if (!response.ok) {
              throw new Error(`Failed to fetch WordPress data: ${response.statusText}`);
            }

            return response.json();
          },
          mountMode: "iframe",
        });

        revokeUrls = Array.from(result.vfsMap.values()).map(
          (url) => () => URL.revokeObjectURL(url),
        );
        if (!revoked) {
          setLoading(false);
        }
      } catch (err) {
        const message = err instanceof Error ? err.message : String(err);
        if (!revoked) {
          setError(message);
          setLoading(false);
        }
      }
    };

    mount();

    return () => {
      revoked = true;
      revokeUrls.forEach((revoke) => revoke());
      revokeUrls = [];
    };
  }, []);

  return (
    <Window
      x='center'
      y='center'
      width='1020'
      height='80%'
      maxWidth='1200'
      maxHeight='90%'
    >
      <div className='flex h-full w-full flex-col'>
        <DraggableHandle className='flex items-center justify-between px-2 py-5 border-b bg-gradient-to-r from-emerald-600 to-teal-600 z-50 pl-20'>
          <span>Ncmaz Faust (WASM)</span>
        </DraggableHandle>
        <ControlButtons />

        <div className='flex-1 min-h-0 overflow-hidden bg-gradient-to-br from-slate-900 to-slate-800'>
          {loading && (
            <div className='flex h-full flex-col items-center justify-center gap-3 text-slate-300'>
              <div className='text-4xl animate-spin'>⏳</div>
              <p>Loading Ncmaz Faust...</p>
            </div>
          )}

          {error && (
            <div className='p-6'>
              <div className='bg-red-900/30 border border-red-600 rounded p-4'>
                <p className='text-red-400 font-mono text-sm'>❌ Error</p>
                <p className='text-red-300 text-xs mt-2'>{error}</p>
              </div>
            </div>
          )}

          <div
            ref={containerRef}
            className='h-full w-full'
            style={{ display: loading || error ? "none" : "block" }}
          />
        </div>
      </div>
    </Window>
  );
}

export default NcmazFaustWasmApp;
