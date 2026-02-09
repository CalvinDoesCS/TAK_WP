import React, { useEffect, useMemo, useRef, useState } from "react";
import { Window, DraggableHandle, ControlButtons } from "@/components/Window";
import { useWasmLoader } from "@/hooks/wasm/useWasmLoader";
import createWasmBridge from "./wasmBridge";
import { fetchWordPressData } from "./utils/dataFetcher";
import { buildAssetMap, collectCSSContent } from "./utils/assetMapBuilder";
import { processHTML } from "./utils/htmlProcessor";
import { normalizePath, verifyBlobContent } from "./utils/helpers";
import type { FileRecord } from "./types";

export default function NcmazFaustWasmApp() {
  const wpApiBase =
    (import.meta as any)?.env?.VITE_WP_API_BASE ?? "http://localhost:8080";
  const wasmBaseUrl = `${wpApiBase}/wp-content/wasm/nacmaz_faust`;

  const wasmLoader = useWasmLoader({
    moduleName: "nacmaz_faust",
    jsUrl: `${wasmBaseUrl}/nacmaz_faust.js`,
    wasmUrl: `${wasmBaseUrl}/nacmaz_faust_bg.wasm`,
  });

  const wasm = wasmLoader.exports as any;
  const bridge = useMemo(() => createWasmBridge(wasm), [wasm]);

  const [fileMap, setFileMap] = useState<Record<string, FileRecord>>({});
  const fileMapRef = useRef(fileMap);
  fileMapRef.current = fileMap;

  const [pageUrls, setPageUrls] = useState<Record<string, string>>({});
  const pageUrlsRef = useRef(pageUrls);
  pageUrlsRef.current = pageUrls;

  const [currentPage, setCurrentPage] = useState<string | null>(null);
  const [pageLoading, setPageLoading] = useState(false);

  useEffect(() => {
    let mounted = true;
    setPageLoading(true);

    const load = async () => {
      // Fetch WordPress data
      const { data: runtimeData, variables: runtimeVars } = await fetchWordPressData({
        wpApiBase,
      });

      const files = bridge.listFiles();
      const htmlFiles = files.filter((f: string) => f.endsWith(".html"));
      console.log(`📁 WASM module provides ${files.length} files`);
      console.log(`📁 HTML files (${htmlFiles.length}):`, htmlFiles);
      console.log(
        `📁 Posts-related files:`,
        files.filter((f: string) => f.includes("posts")),
      );

      // Build asset map and collect CSS
      const assetMap = buildAssetMap({
        files,
        getFile: bridge.getFile.bind(bridge),
        getFileUrl: bridge.getFileUrl.bind(bridge),
        normalizePath,
      });

      const combinedCss = collectCSSContent(files, bridge.getFile.bind(bridge));

      const records: Record<string, FileRecord> = {};
      const htmlPages: string[] = [];

      // Process all files
      for (const p of files) {
        const path = normalizePath(p);
        const r = bridge.getFile(p); // Use original path for bridge call
        if (!r) continue;

        const bytes = r.bytes.slice();
        let url = bridge.getFileUrl(p) ?? ""; // Use original path for bridge call

        // Process HTML files
        if (path.endsWith(".html")) {
          try {
            let html = new TextDecoder().decode(bytes);

            const processedHTML = processHTML({
              html,
              path,
              assetMap,
              combinedCss,
              runtimeData,
              runtimeVars,
              normalizePath,
            });

            const blob = new Blob([processedHTML], { type: "text/html" });
            url = URL.createObjectURL(blob);

            verifyBlobContent(blob, path);
            htmlPages.push(path);
          } catch (err) {
            console.error(`Failed to process ${path}:`, err);
          }
        }

        records[path] = { path, bytes, mime: r.mime, url };
      }

      if (!mounted) return;

      setFileMap(records);

      const urls: Record<string, string> = {};
      for (const hp of htmlPages) {
        urls[hp] = records[hp].url;
      }
      setPageUrls(urls);

      const preferredPage = "/index.html";
      const fallbackPage = "/index.html";
      const defaultPage = records[preferredPage]
        ? preferredPage
        : records[fallbackPage]
          ? fallbackPage
          : null;

      console.log(`📍 Selected: ${defaultPage}`);

      if (defaultPage && !currentPage) {
        setCurrentPage(defaultPage);
      }
    };
    load().finally(() => {
      if (mounted) setPageLoading(false);
    });

    return () => {
      mounted = false;
    };
  }, [bridge, wpApiBase, currentPage]);

  const currentUrl = currentPage
    ? (pageUrlsRef.current[currentPage] ?? fileMapRef.current[currentPage]?.url ?? null)
    : null;

  const htmlPages = Object.keys(pageUrls)
    .filter((k) => k.endsWith(".html"))
    .sort();

  console.log("🎨 Render state:", {
    pageUrlsCount: Object.keys(pageUrls).length,
    htmlPagesCount: htmlPages.length,
    currentPage,
    hasCurrentUrl: !!currentUrl,
    pageLoading,
    wasmReady: bridge?.isReady(),
  });

  return (
    <Window width={900} height={600} x={""} y={""}>
      <ControlButtons className='mt-5' />
      <DraggableHandle className='flex items-center justify-between px-2 py-5 border-b bg-gradient-to-r from-blue-600 to-purple-600 z-50 pl-20'>
        <span>Ncmaz Faust WASM</span>
      </DraggableHandle>

      <div className='w-full h-full'>
        {currentUrl ? (
          <iframe
            src={currentUrl}
            sandbox='allow-scripts allow-same-origin allow-forms allow-modals'
            style={{ width: "100%", height: "100%", border: 0 }}
            title='ncmaz-wasm-page'
          />
        ) : (
          <div style={{ padding: 16 }}>
            {pageLoading ? "Loading..." : "No page available."}
          </div>
        )}
      </div>
    </Window>
  );
}
