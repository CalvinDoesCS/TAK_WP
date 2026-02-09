import type { AssetMap } from "../types";

interface BuildAssetMapParams {
  files: string[];
  getFile: (path: string) => any;
  getFileUrl: (path: string) => string | null;
  normalizePath: (path: string) => string;
}

export function buildAssetMap({
  files,
  getFile,
  getFileUrl,
  normalizePath,
}: BuildAssetMapParams): AssetMap {
  const assetMap: AssetMap = {};

  console.log(`📁 Total files in WASM bundle: ${files.length}`);
  console.log(`📁 Sample files:`, files.slice(0, 10));

  const nextFiles = files.filter(
    (f) => f.includes("/_next/") || f.includes("_next/"),
  );
  console.log(`📁 Files containing '_next': ${nextFiles.length}`);
  if (nextFiles.length > 0) {
    console.log(`📁 Sample _next files:`, nextFiles.slice(0, 5));
  }

  for (const p of files) {
    const path = normalizePath(p);
    const r = getFile(path);
    if (!r) continue;
    const url = getFileUrl(path) ?? "";

    // Add all non-HTML files to asset map
    if (!path.endsWith(".html") && url) {
      assetMap[path] = url;
      if (path.includes("/_next/")) {
        console.log(
          `✅ Added to assetMap: ${path} → ${url.substring(0, 50)}...`,
        );
      }
    }
  }

  console.log(`📦 Built asset map with ${Object.keys(assetMap).length} entries`);
  const nextKeys = Object.keys(assetMap).filter((k) => k.includes("/_next/"));
  console.log(`📦 Asset map has ${nextKeys.length} /_next/ files`);

  if (nextKeys.length > 0) {
    console.log(`✅ Sample /_next/ keys in assetMap:`, nextKeys.slice(0, 5));
  } else {
    console.error(`❌ NO /_next/ files in asset map!`);
    console.log(`📋 All assetMap keys:`, Object.keys(assetMap));
  }

  return assetMap;
}

export function collectCSSContent(
  files: string[],
  getFile: (path: string) => any,
): string {
  const cssContents: string[] = [];
  for (const p of files) {
    if (p.endsWith(".css")) {
      const r = getFile(p);
      if (r) cssContents.push(new TextDecoder().decode(r.bytes));
    }
  }
  return cssContents.join("\n");
}
