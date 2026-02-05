import React, { useEffect, useMemo, useState } from "react";
import { Window, DraggableHandle, ControlButtons } from "@/components/Window";
import { useWasmLoader } from "@/hooks/wasm";

interface FileEntry {
  path: string;
  size: number | null;
  mime: string | null;
}

interface FileRecord {
  path: string;
  bytes: Uint8Array;
  mime: string | null;
  url: string;
}

function readBytes(memory: WebAssembly.Memory, ptr: number, len: number) {
  return new Uint8Array(memory.buffer, ptr, len);
}

function readString(memory: WebAssembly.Memory, ptr: number, len: number) {
  return new TextDecoder().decode(readBytes(memory, ptr, len));
}

function normalizePath(path: string) {
  const noHash = path.split("#")[0] ?? path;
  const noQuery = noHash.split("?")[0] ?? noHash;
  if (!noQuery.startsWith("/")) {
    return `/${noQuery}`;
  }
  return noQuery;
}

function resolveRelativePath(basePath: string, relativePath: string) {
  if (relativePath.startsWith("/")) {
    return normalizePath(relativePath);
  }
  const baseDir = basePath.slice(0, basePath.lastIndexOf("/") + 1);
  return normalizePath(`${baseDir}${relativePath}`);
}

function tryDecodePath(path: string) {
  if (!/%[0-9A-Fa-f]{2}/.test(path)) return null;
  try {
    return decodeURIComponent(path);
  } catch {
    return null;
  }
}

function encodeFirstTwoSegments(path: string) {
  const normalized = normalizePath(path);
  const segments = normalized.split("/").filter(Boolean);
  if (segments.length < 2) return null;
  const hasTrailingSlash = normalized.endsWith("/");
  const encodedBase = `${segments[0]}%2F${segments[1]}`;
  const rest = segments.slice(2);
  let rebuilt = `/${encodedBase}`;
  if (rest.length > 0) {
    rebuilt += `/${rest.join("/")}`;
  } else if (hasTrailingSlash) {
    rebuilt += "/";
  }
  return rebuilt;
}

function tryResolvePath(records: Record<string, FileRecord>, path: string) {
  if (records[path]) return records[path];

  if (path === "/") {
    return records["/index.html"] ?? null;
  }

  if (path.endsWith("/")) {
    const indexPath = `${path}index.html`;
    return records[indexPath] ?? null;
  }

  const htmlPath = `${path}.html`;
  if (records[htmlPath]) return records[htmlPath];

  const indexPath = `${path}/index.html`;
  return records[indexPath] ?? null;
}

function resolveToExistingAsset(
  records: Record<string, FileRecord>,
  basePath: string,
  value: string,
): FileRecord | null {
  const normalized = resolveRelativePath(basePath, value);
  const direct = tryResolvePath(records, normalized);
  if (direct) return direct;

  const decoded = tryDecodePath(normalized);
  if (decoded && decoded !== normalized) {
    const decodedMatch = tryResolvePath(records, decoded);
    if (decodedMatch) return decodedMatch;
  }

  const encoded = encodeFirstTwoSegments(normalized);
  if (encoded && encoded !== normalized) {
    return tryResolvePath(records, encoded);
  }

  return null;
}

function resolveAbsoluteAsset(records: Record<string, FileRecord>, value: string) {
  try {
    const parsed = new URL(value);
    const normalized = normalizePath(parsed.pathname);
    const direct = tryResolvePath(records, normalized);
    if (direct) return direct;

    const decoded = tryDecodePath(normalized);
    if (decoded && decoded !== normalized) {
      const decodedMatch = tryResolvePath(records, decoded);
      if (decodedMatch) return decodedMatch;
    }

    const encoded = encodeFirstTwoSegments(normalized);
    if (encoded && encoded !== normalized) {
      return tryResolvePath(records, encoded);
    }
  } catch {
    return null;
  }
  return null;
}

function shouldReportMissingAsset(path: string) {
  if (!path) return false;
  if (
    path.startsWith("/tag/") ||
    path.startsWith("/author/") ||
    path.startsWith("/category/") ||
    path.startsWith("/dashboard/") ||
    path.startsWith("/documents/") ||
    path === "/privacy-policy"
  ) {
    return false;
  }
  if (path.startsWith("/_next/")) return true;
  if (path.startsWith("/images/") || path.startsWith("/favicons/")) return true;
  return /\.[a-z0-9]{2,6}$/i.test(path);
}

function getBasename(path: string) {
  const normalized = normalizePath(path);
  const parts = normalized.split("/").filter(Boolean);
  return parts.length ? parts[parts.length - 1] : "";
}

function isExternalUrl(url: string) {
  return /^(https?:|data:|blob:|mailto:|tel:|#)/i.test(url);
}

function loadFileFromWasm(
  wasm: any,
  path: string,
  onMissing?: (path: string) => void,
): { bytes: Uint8Array; mime: string | null } | null {
  if (
    !wasm?.memory ||
    !wasm?.alloc ||
    !wasm?.dealloc ||
    !wasm?.get_file ||
    !wasm?.get_last_file_ptr ||
    !wasm?.get_last_file_len ||
    !wasm?.get_last_file_mime_ptr ||
    !wasm?.get_last_file_mime_len
  ) {
    return null;
  }

  try {
    const memory = wasm.memory as WebAssembly.Memory;
    const encoder = new TextEncoder();
    const normalizedPath = path.startsWith("/") ? path : `/${path}`;
    const pathBytes = encoder.encode(normalizedPath);
    const ptr = wasm.alloc(pathBytes.length) as number;
    readBytes(memory, ptr, pathBytes.length).set(pathBytes);

    const ok = wasm.get_file(ptr, pathBytes.length) as number;
    wasm.dealloc(ptr, pathBytes.length);

    if (!ok) {
      onMissing?.(normalizedPath);
      return null;
    }

    const filePtr = wasm.get_last_file_ptr() as number;
    const fileLen = wasm.get_last_file_len() as number;
    const mimePtr = wasm.get_last_file_mime_ptr() as number;
    const mimeLen = wasm.get_last_file_mime_len() as number;

    const bytes = readBytes(memory, filePtr, fileLen).slice();
    const mime = mimePtr && mimeLen ? readString(memory, mimePtr, mimeLen) : null;
    return { bytes, mime };
  } catch (err) {
    console.error("Failed to load file from WASM:", err);
    return null;
  }
}

export default function NcmazFaustWasmApp() {
  const wpApiBase =
    (import.meta as any)?.env?.VITE_WP_API_BASE ?? "http://localhost:8080";

  const wasmUrls = useMemo(() => {
    const base = wpApiBase.replace(/\/$/, "");
    return {
      jsUrl: `${base}/wp-content/wasm/ncmaz_faust/ncmaz_faust_sidecar.js`,
      wasmUrl: `${base}/wp-content/wasm/ncmaz_faust/ncmaz_faust_sidecar_bg.wasm`,
    };
  }, [wpApiBase]);

  const {
    exports: wasm,
    loading,
    error,
  } = useWasmLoader({
    moduleName: "ncmaz_faust/ncmaz_faust_sidecar",
    wasmUrl: wasmUrls.wasmUrl,
    jsUrl: wasmUrls.jsUrl,
  });

  const [fileEntries, setFileEntries] = useState<FileEntry[]>([]);
  const [fileMap, setFileMap] = useState<Record<string, FileRecord>>({});
  const [pagePaths, setPagePaths] = useState<string[]>([]);
  const [pageUrls, setPageUrls] = useState<Record<string, string>>({});
  const [currentPage, setCurrentPage] = useState<string | null>(null);
  const [pageLoading, setPageLoading] = useState(false);
  const [wpDataStatus, setWpDataStatus] = useState<string | null>(null);
  const loadIdRef = React.useRef(0);
  const activeUrlsRef = React.useRef<string[]>([]);
  const fileMapRef = React.useRef<Record<string, FileRecord>>({});
  const pageUrlsRef = React.useRef<Record<string, string>>({});
  const missingAssetsRef = React.useRef<Set<string>>(new Set());
  const [missingAssets, setMissingAssets] = useState<string[]>([]);

  const debugEnabled = React.useMemo(() => {
    try {
      const envFlag = (import.meta as any)?.env?.VITE_WASM_DEBUG === "true";
      const storageFlag = localStorage.getItem("ncmaz-wasm-debug") === "1";
      return envFlag || storageFlag;
    } catch {
      return false;
    }
  }, []);

  const recordMissingAsset = React.useCallback(
    (label: string) => {
      if (missingAssetsRef.current.has(label)) return;
      missingAssetsRef.current.add(label);
      setMissingAssets(Array.from(missingAssetsRef.current));
      if (debugEnabled) {
        console.warn("Missing embedded asset:", label);
      }
    },
    [debugEnabled],
  );

  const navigateToPath = React.useCallback(
    (href: string) => {
      const records = fileMapRef.current;
      const urls = pageUrlsRef.current;
      if (!records || Object.keys(records).length === 0) return;

      let target: FileRecord | null = null;
      if (/^https?:/i.test(href)) {
        target = resolveAbsoluteAsset(records, href);
      } else {
        target = resolveToExistingAsset(records, currentPage ?? "/", href);
      }

      if (!target && href.startsWith("/")) {
        target = tryResolvePath(records, normalizePath(href));
      }

      if (target?.path && urls[target.path]) {
        setCurrentPage(target.path);
      }
    },
    [currentPage],
  );

  useEffect(() => {
    const handleMessage = (event: MessageEvent) => {
      const data = event.data as { type?: string; href?: string } | null;
      if (!data || data.type !== "ncmaz:navigate" || !data.href) return;
      navigateToPath(data.href);
    };

    window.addEventListener("message", handleMessage);
    return () => window.removeEventListener("message", handleMessage);
  }, [navigateToPath]);

  useEffect(() => {
    if (
      !wasm?.memory ||
      !wasm?.get_file_count ||
      !wasm?.get_file_path_ptr ||
      !wasm?.get_file_path_len
    ) {
      return;
    }

    try {
      const count = wasm.get_file_count() as number;
      const memory = wasm.memory as WebAssembly.Memory;
      const entries: FileEntry[] = [];

      for (let i = 0; i < count; i++) {
        const ptr = wasm.get_file_path_ptr(i) as number;
        const len = wasm.get_file_path_len(i) as number;
        if (!ptr || !len) continue;
        const path = readString(memory, ptr, len);
        entries.push({ path, size: null, mime: null });
      }

      setFileEntries(entries);
    } catch (err) {
      console.error("Failed to read file list from WASM:", err);
    }
  }, [wasm]);

  useEffect(() => {
    if (!wasm?.receive_wp_data || !wasm?.alloc || !wasm?.dealloc || !wasm?.memory) {
      return;
    }

    const controller = new AbortController();
    const wpBase = wpApiBase.replace(/\/$/, "");
    const endpoint =
      (import.meta as any)?.env?.VITE_WP_DATA_ENDPOINT ??
      `${wpBase}/wp-json/wp/v2/posts?per_page=20&_embed=1`;

    const sendData = async () => {
      try {
        setWpDataStatus("Loading WP data…");
        const response = await fetch(endpoint, { signal: controller.signal });
        if (!response.ok) {
          throw new Error(`WP data request failed: ${response.status}`);
        }
        const json = await response.json();
        const payload = JSON.stringify(json);
        const encoder = new TextEncoder();
        const bytes = encoder.encode(payload);

        const ptr = wasm.alloc(bytes.length) as number;
        readBytes(wasm.memory as WebAssembly.Memory, ptr, bytes.length).set(bytes);
        const ok = wasm.receive_wp_data(ptr, bytes.length) as number;
        wasm.dealloc(ptr, bytes.length);

        if (!ok) {
          throw new Error("WASM rejected WP data");
        }

        setWpDataStatus(`Injected ${bytes.length} bytes of WP data.`);
      } catch (err) {
        if ((err as any)?.name === "AbortError") return;
        console.error("Failed to inject WP data into WASM:", err);
        setWpDataStatus("Failed to inject WP data.");
      }
    };

    sendData();

    return () => controller.abort();
  }, [wasm, wpApiBase]);

  const handleLoadFile = (entry: FileEntry) => {
    if (
      !wasm?.memory ||
      !wasm?.alloc ||
      !wasm?.dealloc ||
      !wasm?.get_file ||
      !wasm?.get_last_file_ptr ||
      !wasm?.get_last_file_len ||
      !wasm?.get_last_file_mime_ptr ||
      !wasm?.get_last_file_mime_len
    ) {
      return;
    }

    try {
      const memory = wasm.memory as WebAssembly.Memory;
      const encoder = new TextEncoder();
      const normalizedPath = entry.path.startsWith("/") ? entry.path : `/${entry.path}`;
      const pathBytes = encoder.encode(normalizedPath);
      const ptr = wasm.alloc(pathBytes.length) as number;
      readBytes(memory, ptr, pathBytes.length).set(pathBytes);

      const ok = wasm.get_file(ptr, pathBytes.length) as number;
      wasm.dealloc(ptr, pathBytes.length);

      if (!ok) {
        return;
      }

      const filePtr = wasm.get_last_file_ptr() as number;
      const fileLen = wasm.get_last_file_len() as number;
      const mimePtr = wasm.get_last_file_mime_ptr() as number;
      const mimeLen = wasm.get_last_file_mime_len() as number;

      const bytes = readBytes(memory, filePtr, fileLen);
      const mime = mimePtr && mimeLen ? readString(memory, mimePtr, mimeLen) : null;

      const path = normalizePath(entry.path);
      const bytesCopy = bytes.slice();
      const url = URL.createObjectURL(new Blob([bytesCopy], { type: mime ?? "" }));
      setFileMap((prev) => ({
        ...prev,
        [path]: {
          path,
          bytes: bytesCopy,
          mime,
          url,
        },
      }));
    } catch (err) {
      console.error("Failed to load file from WASM:", err);
    }
  };

  useEffect(() => {
    if (
      !wasm?.memory ||
      !wasm?.alloc ||
      !wasm?.dealloc ||
      !wasm?.get_file ||
      !wasm?.get_last_file_ptr ||
      !wasm?.get_last_file_len ||
      !wasm?.get_last_file_mime_ptr ||
      !wasm?.get_last_file_mime_len ||
      fileEntries.length === 0
    ) {
      return;
    }

    const loadId = ++loadIdRef.current;
    let isMounted = true;
    const createdUrls: string[] = [];

    const loadAllFiles = () => {
      setPageLoading(true);
      const records: Record<string, FileRecord> = {};
      const htmlPaths: string[] = [];

      for (const entry of fileEntries) {
        const normalizedPath = normalizePath(entry.path);
        const result = loadFileFromWasm(wasm, normalizedPath, (missingPath) => {
          recordMissingAsset(`missing-file:${missingPath}`);
        });
        if (!result) {
          continue;
        }
        const { bytes, mime } = result;

        const bytesStrict = new Uint8Array(bytes.buffer as ArrayBuffer);

        const url = URL.createObjectURL(new Blob([bytesStrict], { type: mime ?? "" }));
        createdUrls.push(url);

        records[normalizedPath] = {
          path: normalizedPath,
          bytes: bytesStrict,
          mime,
          url,
        };

        if (mime === "text/html" || normalizedPath.endsWith(".html")) {
          htmlPaths.push(normalizedPath);
        }
      }

      if (!records["/index.html"]) {
        const indexResult = loadFileFromWasm(wasm, "/index.html", (missingPath) => {
          recordMissingAsset(`missing-file:${missingPath}`);
        });
        if (indexResult) {
          const bytesStrict2 = new Uint8Array(indexResult.bytes.buffer as ArrayBuffer);
          const indexUrl = URL.createObjectURL(
            new Blob([bytesStrict2], { type: indexResult.mime ?? "text/html" }),
          );
          createdUrls.push(indexUrl);
          records["/index.html"] = {
            path: "/index.html",
            bytes: bytesStrict2,
            mime: indexResult.mime ?? "text/html",
            url: indexUrl,
          };
          if (!htmlPaths.includes("/index.html")) {
            htmlPaths.unshift("/index.html");
          }
        }
      }

      if (!isMounted || loadId !== loadIdRef.current) {
        return;
      }

      activeUrlsRef.current = createdUrls;

      const basenameIndex = new Map<string, FileRecord[]>();
      Object.values(records).forEach((record) => {
        const basename = getBasename(record.path);
        if (!basename) return;
        const list = basenameIndex.get(basename) ?? [];
        list.push(record);
        basenameIndex.set(basename, list);
      });

      const resolveByBasename = (value: string) => {
        const basename = getBasename(value);
        if (!basename) return null;
        const matches = basenameIndex.get(basename);
        if (!matches || matches.length !== 1) return null;
        return matches[0];
      };

      const runtimeAssetMap: Record<string, string> = {};
      Object.entries(records).forEach(([path, record]) => {
        if (
          path.startsWith("/_next/") ||
          path.startsWith("/images/") ||
          path.startsWith("/favicons/") ||
          path === "/logo.png" ||
          path === "/logo-light.png"
        ) {
          runtimeAssetMap[path] = record.url;
        }
      });

      const rewriteAssetUrl = (basePath: string, value: string) => {
        if (!value) return value;
        if (isExternalUrl(value)) {
          const externalAsset = resolveAbsoluteAsset(records, value);
          if (externalAsset?.url) return externalAsset.url;
          const basenameMatch = resolveByBasename(value);
          return basenameMatch?.url ?? value;
        }
        const asset = resolveToExistingAsset(records, basePath, value);
        if (asset?.url) return asset.url;
        const basenameMatch = resolveByBasename(value);
        if (basenameMatch?.url) return basenameMatch.url;
        if (!asset?.url) {
          const normalized = resolveRelativePath(basePath, value);
          if (shouldReportMissingAsset(normalized)) {
            recordMissingAsset(`missing-asset:${normalized}`);
          }
        }
        return value;
      };

      for (const [path, record] of Object.entries(records)) {
        if (record.mime !== "text/css" && !path.endsWith(".css")) {
          continue;
        }

        try {
          const cssText = new TextDecoder().decode(record.bytes);
          let rewrittenCss = cssText.replace(
            /url\((['"]?)([^'")]+)\1\)/g,
            (match, quote, urlValue) => {
              const trimmed = urlValue.trim();
              const rewritten = rewriteAssetUrl(path, trimmed);
              if (rewritten === trimmed) return match;
              const safeQuote = quote || '"';
              return `url(${safeQuote}${rewritten}${safeQuote})`;
            },
          );

          rewrittenCss = rewrittenCss.replace(
            /@import\s+(?:url\()?['"]?([^'")]+)['"]?\)?;/g,
            (match, importUrl) => {
              const rewritten = rewriteAssetUrl(path, importUrl.trim());
              if (rewritten === importUrl.trim()) return match;
              return `@import url("${rewritten}");`;
            },
          );

          if (rewrittenCss !== cssText) {
            const cssUrl = URL.createObjectURL(
              new Blob([rewrittenCss], { type: "text/css" }),
            );
            createdUrls.push(cssUrl);
            records[path] = {
              ...record,
              bytes: new TextEncoder().encode(rewrittenCss),
              url: cssUrl,
            };
          }
        } catch (err) {
          console.error("Failed to rewrite CSS:", path, err);
        }
      }

      setFileMap(records);
      fileMapRef.current = records;
      setPagePaths(htmlPaths);

      const newPageUrls: Record<string, string> = {};
      for (const pagePath of htmlPaths) {
        const pageRecord = records[pagePath];
        if (!pageRecord) continue;

        const html = new TextDecoder().decode(pageRecord.bytes);
        const doc = new DOMParser().parseFromString(html, "text/html");

        const updateAttr = (
          element: Element,
          attr: "src" | "href" | "poster" | "data-src" | "data-href",
        ) => {
          const value = element.getAttribute(attr);
          if (!value) return;
          const rewritten = rewriteAssetUrl(pagePath, value);
          if (rewritten !== value) {
            element.setAttribute(attr, rewritten);
          } else if (!isExternalUrl(value)) {
            if (element.tagName === "A" && attr === "href") {
              return;
            }
            const normalized = resolveRelativePath(pagePath, value);
            if (shouldReportMissingAsset(normalized)) {
              recordMissingAsset(`missing-asset:${normalized}`);
            }
          }
        };

        const updateSrcSet = (element: Element, attr: "srcset" | "data-srcset") => {
          const value = element.getAttribute(attr);
          if (!value) return;
          const rewritten = value
            .split(",")
            .map((entry) => {
              const trimmed = entry.trim();
              if (!trimmed) return trimmed;
              const [urlPart, descriptor] = trimmed.split(/\s+/, 2);
              if (!urlPart) return trimmed;
              const rewritten = rewriteAssetUrl(pagePath, urlPart);
              if (rewritten === urlPart) return trimmed;
              return descriptor ? `${rewritten} ${descriptor}` : rewritten;
            })
            .join(", ");

          element.setAttribute(attr, rewritten);
        };

        const updateInlineStyle = (element: Element) => {
          const style = element.getAttribute("style");
          if (!style) return;
          const rewritten = style.replace(
            /url\((['"]?)([^'")]+)\1\)/g,
            (match, quote, urlValue) => {
              const trimmed = urlValue.trim();
              const rewrittenUrl = rewriteAssetUrl(pagePath, trimmed);
              if (rewrittenUrl === trimmed) return match;
              const safeQuote = quote || '"';
              return `url(${safeQuote}${rewrittenUrl}${safeQuote})`;
            },
          );
          element.setAttribute("style", rewritten);
        };

        doc.querySelectorAll("[src]").forEach((el) => updateAttr(el, "src"));
        doc.querySelectorAll("[href]").forEach((el) => updateAttr(el, "href"));
        doc.querySelectorAll("[poster]").forEach((el) => updateAttr(el, "poster"));
        doc.querySelectorAll("[data-src]").forEach((el) => updateAttr(el, "data-src"));
        doc.querySelectorAll("[data-href]").forEach((el) => updateAttr(el, "data-href"));
        doc.querySelectorAll("[srcset]").forEach((el) => updateSrcSet(el, "srcset"));
        doc
          .querySelectorAll("[data-srcset]")
          .forEach((el) => updateSrcSet(el, "data-srcset"));
        doc.querySelectorAll("[style]").forEach((el) => updateInlineStyle(el));

        const bootstrapScript = doc.createElement("script");
        bootstrapScript.textContent = `(() => {
  const assetMap = ${JSON.stringify(runtimeAssetMap)};
  const isExternal = (href) => /^(https?:|mailto:|tel:|#|blob:|data:)/i.test(href || "");
  const normalizeTarget = (target) => {
    if (!target) return "";
    if (typeof target === "string") return target;
    if (typeof target === "object") {
      const pathname = target.pathname || "";
      let search = "";
      if (typeof target.query === "string") {
        search = target.query.startsWith("?") ? target.query.slice(1) : target.query;
      } else if (target.query && typeof target.query === "object") {
        const params = new URLSearchParams();
        Object.entries(target.query).forEach(([key, value]) => {
          if (Array.isArray(value)) {
            value.forEach((item) => params.append(key, String(item)));
          } else if (value != null) {
            params.set(key, String(value));
          }
        });
        search = params.toString();
      }
      const hash = target.hash
        ? String(target.hash).startsWith("#")
          ? target.hash
          : "#" + target.hash
        : "";
      return pathname + (search ? "?" + search : "") + hash;
    }
    return String(target);
  };
  const resolvePath = (href) => {
    try {
      return new URL(href, "https://ncmaz.local").pathname;
    } catch {
      return href || "";
    }
  };
  const rewrite = (href) => {
    if (!href || isExternal(href)) return href;
    const path = resolvePath(href);
    return assetMap[path] || href;
  };
  const originalSetAttribute = Element.prototype.setAttribute;
  Element.prototype.setAttribute = function(name, value) {
    if ((name === "src" || name === "href") && typeof value === "string") {
      return originalSetAttribute.call(this, name, rewrite(value));
    }
    return originalSetAttribute.call(this, name, value);
  };

  const patchProp = (proto, prop) => {
    const desc = Object.getOwnPropertyDescriptor(proto, prop);
    if (!desc || !desc.set) return;
    Object.defineProperty(proto, prop, {
      ...desc,
      set(value) {
        if (typeof value === "string") {
          try {
            return desc.set.call(this, rewrite(value));
          } catch {
            return desc.set.call(this, value);
          }
        }
        return desc.set.call(this, value);
      },
    });
  };

  patchProp(HTMLScriptElement.prototype, "src");
  patchProp(HTMLLinkElement.prototype, "href");
  patchProp(HTMLImageElement.prototype, "src");
  patchProp(HTMLSourceElement.prototype, "src");

  const pageBase = ${JSON.stringify(pagePath)};
  const resolveHref = (href) => {
    try {
      const normalized = normalizeTarget(href);
      if (!normalized) return "";
      const resolved = new URL(normalized, "https://ncmaz.local" + pageBase);
      return resolved.pathname + resolved.search + resolved.hash;
    } catch {
      return href;
    }
  };

  const postNavigate = (href) => {
    if (!href || href === "#") return;
    window.parent?.postMessage({ type: "ncmaz:navigate", href }, "*");
  };

  const patchHistory = (method) => {
    const original = history[method];
    history[method] = function(state, title, url) {
      if (!url) return original.call(this, state, title, url);
      const resolved = resolveHref(String(url));
      if (!resolved) return original.call(this, state, title, url);
      postNavigate(resolved);
      return null;
    };
  };

  patchHistory("pushState");
  patchHistory("replaceState");

  const originalAssign = location.assign.bind(location);
  const originalReplace = location.replace.bind(location);
  location.assign = (url) => {
    const resolved = resolveHref(String(url));
    if (!resolved) return;
    postNavigate(resolved);
  };
  location.replace = (url) => {
    const resolved = resolveHref(String(url));
    if (!resolved) return;
    postNavigate(resolved);
  };

  const patchNextRouter = (router) => {
    if (!router) return;
    const wrap = (name) => {
      const original = router[name];
      if (typeof original !== "function") return;
      router[name] = function(href, as, opts) {
        const target = as || href;
        const resolved = resolveHref(target);
        if (resolved) {
          postNavigate(resolved);
          return Promise.resolve(true);
        }
        return Promise.resolve(false);
      };
    };
    wrap("push");
    wrap("replace");
  };

  const tryPatchRouter = () => {
    try {
      const nextRouter = window.next && window.next.router;
      patchNextRouter(nextRouter);
      const globalRouter = window.__NEXT_ROUTER__;
      patchNextRouter(globalRouter);
    } catch {
      // ignore
    }
  };

  tryPatchRouter();
  setTimeout(tryPatchRouter, 0);
  document.addEventListener(
    "click",
    (event) => {
      if (event.defaultPrevented) return;
      if (event.button !== 0) return;
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      const target = event.target;
      const anchor = target?.closest?.("a");
      let href = anchor?.getAttribute?.("href") || anchor?.href || "";
      if (!href) {
        const dataEl = target?.closest?.("[data-href],[data-url],[data-link],[data-to]");
        href =
          dataEl?.getAttribute?.("data-href") ||
          dataEl?.getAttribute?.("data-url") ||
          dataEl?.getAttribute?.("data-link") ||
          dataEl?.getAttribute?.("data-to") ||
          "";
      }
      if (!href || isExternal(href)) return;
      if (anchor?.target && anchor.target !== "_self") return;
      const resolved = resolveHref(href);
      postNavigate(resolved);
      event.preventDefault();
    },
    true,
  );
})();`;
        if (doc.head?.firstChild) {
          doc.head.insertBefore(bootstrapScript, doc.head.firstChild);
        } else if (doc.head) {
          doc.head.appendChild(bootstrapScript);
        } else {
          doc.documentElement.appendChild(bootstrapScript);
        }

        const serialized = doc.documentElement.outerHTML;
        const pageUrl = URL.createObjectURL(
          new Blob([serialized], { type: "text/html" }),
        );
        createdUrls.push(pageUrl);
        newPageUrls[pagePath] = pageUrl;
      }

      setPageUrls(newPageUrls);
      pageUrlsRef.current = newPageUrls;
      if (debugEnabled) {
        console.info("WASM assets loaded", {
          files: Object.keys(records).length,
          pages: htmlPaths.length,
          missing: missingAssetsRef.current.size,
        });
      }

      const preferredCandidates = [
        "/posts/index.html",
        "/posts/",
        "/posts",
        "/index.html",
      ];
      let defaultPage: string | null = null;
      for (const candidate of preferredCandidates) {
        const resolved = resolveToExistingAsset(records, "/", candidate);
        if (resolved?.path && newPageUrls[resolved.path]) {
          defaultPage = resolved.path;
          break;
        }
      }

      if (!defaultPage) {
        defaultPage =
          htmlPaths.find((path) => path.endsWith("/index.html")) ??
          htmlPaths.find((path) => path.endsWith("index.html")) ??
          htmlPaths[0] ??
          null;
      }

      if (!currentPage && defaultPage) {
        setCurrentPage(defaultPage);
      }

      setPageLoading(false);
    };

    loadAllFiles();

    return () => {
      isMounted = false;
    };
  }, [fileEntries, wasm]);

  return (
    <Window
      x='center'
      y='center'
      width='820'
      height='620'
      maxWidth='1000'
      maxHeight='900'
    >
      <DraggableHandle className='flex items-center justify-between px-2 py-5 border-b bg-gradient-to-r from-emerald-600 to-teal-600 z-50 pl-20'>
        <span>Ncmaz Faust WASM</span>
      </DraggableHandle>
      <ControlButtons />

      <div className='flex-1 overflow-hidden p-4 bg-gradient-to-br from-slate-900 to-slate-800'>
        {loading && (
          <div className='flex flex-col items-center justify-center h-full gap-3'>
            <div className='text-4xl animate-spin'>⏳</div>
            <p className='text-slate-300'>Loading Ncmaz Faust...</p>
          </div>
        )}

        {error && (
          <div className='bg-red-900/30 border border-red-600 rounded p-4'>
            <p className='text-red-400 font-mono text-sm'>❌ Error</p>
            <p className='text-red-300 text-xs mt-2'>{error}</p>
          </div>
        )}

        {!loading && !error && (
          <div className='bg-slate-900/60 rounded-lg p-3 border border-slate-700 h-full'>
            {pageLoading && <p className='text-slate-400 text-sm mb-2'>Loading page…</p>}
            {wpDataStatus && (
              <p className='text-slate-400 text-xs mb-2'>{wpDataStatus}</p>
            )}
            {!currentPage && <p className='text-slate-400 text-sm'>No page loaded.</p>}
            {currentPage && pageUrls[currentPage] && (
              <iframe
                title='Ncmaz Faust Posts'
                src={pageUrls[currentPage]}
                className='w-full h-[560px] rounded border border-slate-700 bg-white'
                sandbox='allow-same-origin allow-scripts allow-forms allow-popups allow-popups-to-escape-sandbox'
              />
            )}
          </div>
        )}
      </div>
    </Window>
  );
}
