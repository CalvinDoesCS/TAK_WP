import { CSP_POLICY } from "../constants";
import { createFrontPageData } from "../config/frontPageData";
import { generateBootstrapScript } from "./bootstrapScript";
import type { AssetMap, WordPressData } from "../types";

interface ProcessHTMLParams {
  html: string;
  path: string;
  assetMap: AssetMap;
  combinedCss: string;
  runtimeData: WordPressData;
  runtimeVars: Record<string, string>;
  normalizePath: (path: string) => string;
}

export function processHTML({
  html,
  path,
  assetMap,
  combinedCss,
  runtimeData,
  runtimeVars,
  normalizePath,
}: ProcessHTMLParams): string {
  // 1. Inject runtime data into __NEXT_DATA__ script
  const nextDataMatch = html.match(
    /<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s,
  );

  if (nextDataMatch && nextDataMatch[1]) {
    try {
      const parsed = JSON.parse(nextDataMatch[1]);

      // For index.html (front page), add special metadata for page template
      if (path === "/index.html") {
        // Use real page data from WordPress if available, otherwise create synthetic data
        const frontPageData = runtimeData.page || createFrontPageData(runtimeData);

        // PRESERVE original props structure, just add our data
        parsed.props.pageProps = {
          ...parsed.props.pageProps,
          data: {
            ...runtimeData,
            page: frontPageData,
          },
          __PAGE_VARIABLES__: runtimeVars,
        };
        // Add __SEED_NODE__ at props level (not pageProps)
        parsed.props.__SEED_NODE__ = frontPageData;
      } else {
        // For other pages, inject the runtime data normally
        parsed.props.pageProps.data = runtimeData;
        parsed.props.pageProps.__PAGE_VARIABLES__ = runtimeVars;
      }

      const updated = `<script id="__NEXT_DATA__" type="application/json">${JSON.stringify(parsed)}</script>`;
      html = html.replace(nextDataMatch[0], updated);
      
      console.log(`✅ Injected data into ${path}`, {
        posts: runtimeData?.posts?.nodes?.length ?? 0,
        menus: runtimeData?.primaryMenuItems?.nodes?.length ?? 0,
        page: path === "/index.html" && runtimeData.page ? "✓" : "-",
      });
    } catch (e) {
      console.warn(`Failed to parse __NEXT_DATA__ in ${path}:`, e);
    }
  } else {
    console.error(`❌ No __NEXT_DATA__ script tag found in ${path}`);
  }

  // 2. Rewrite static asset URLs
  html = html.replace(/\b(?:href|src)=(["'])([^"']+)\1/g, (match, quote, url) => {
    if (
      url.startsWith("http") ||
      url.startsWith("//") ||
      url.startsWith("data:") ||
      url.startsWith("blob:")
    ) {
      return match;
    }
    const normalized = normalizePath(url);
    if (assetMap[normalized]) {
      const attrName = match.includes("href=") ? "href" : "src";
      if (path === "/index.html" && url.includes("/_next/")) {
        console.log(
          `🔄 Rewriting ${url} → ${assetMap[normalized].substring(0, 50)}...`,
        );
      }
      return `${attrName}=${quote}${assetMap[normalized]}${quote}`;
    } else {
      if (path === "/index.html" && url.includes("/_next/")) {
        console.warn(`⚠️ Asset not found in map: ${url}`);
      }
    }
    return match;
  });

  // 3. Remove existing CSP meta tags
  html = html.replace(
    /<meta[^>]*http-equiv=["']Content-Security-Policy["'][^>]*>/gi,
    "",
  );
  console.log("🗑️ Removed existing CSP meta tags");

  // 4. Inject runtime bootstrap script
  const bootstrap = generateBootstrapScript(assetMap);
  html = html.replace(/<\/head>/i, `${bootstrap}</head>`);

  // 5. Inject CSS and CSP into head
  const docStart = html.search(/<head[^>]*>/i);

  // For index.html, inject early __NEXT_DATA__ initializer
  let earlyInit = "";
  if (path === "/index.html") {
    const nextDataMatch = html.match(
      /<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s,
    );
    if (nextDataMatch) {
      try {
        const parsed = JSON.parse(nextDataMatch[1]);
        earlyInit = `<script>window.__NEXT_DATA__=${JSON.stringify(parsed)};</script>`;
        console.log("✅ Added early __NEXT_DATA__ initializer for index.html");
      } catch (e) {
        console.error("Failed to create early init:", e);
      }
    }
  }

  const styled =
    docStart !== -1
      ? html.replace(
          /<head[^>]*>/i,
          (m) =>
            `${m}<meta http-equiv="Content-Security-Policy" content="${CSP_POLICY}">${earlyInit}<style>${combinedCss}</style>`,
        )
      : `<head><meta http-equiv="Content-Security-Policy" content="${CSP_POLICY}">${earlyInit}<style>${combinedCss}</style></head>\n` +
        html;

  console.log(`Final HTML length for ${path}: ${styled.length}`);
  console.log(
    `Has __NEXT_DATA__ in final HTML: ${styled.includes("__NEXT_DATA__")}`,
  );

  return styled;
}
