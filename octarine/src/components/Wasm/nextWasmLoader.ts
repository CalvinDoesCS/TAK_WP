export type NextWasmMountMode = "iframe" | "shadow";

export interface NextWasmLoaderOptions {
  wasmUrl: string;
  container: HTMLElement;
  wpData?: unknown;
  wpDataProvider?: () => Promise<unknown>;
  mountMode?: NextWasmMountMode;
  onError?: (error: string) => void;
}

interface NextWasmExports extends WebAssembly.Exports {
  memory: WebAssembly.Memory;
  alloc: (len: number) => number;
  dealloc: (ptr: number, len: number) => void;
  get_file_count: () => number;
  get_file_path_ptr: (index: number) => number;
  get_file_path_len: (index: number) => number;
  get_file: (ptr: number, len: number) => number;
  get_last_file_ptr: () => number;
  get_last_file_len: () => number;
  get_last_file_mime_ptr: () => number;
  get_last_file_mime_len: () => number;
  receive_wp_data: (ptr: number, len: number) => number;
  get_wp_data_ptr: () => number;
  get_wp_data_len: () => number;
}

const textEncoder = new TextEncoder();
const textDecoder = new TextDecoder();

function readBytes(memory: WebAssembly.Memory, ptr: number, len: number): Uint8Array {
  return new Uint8Array(memory.buffer, ptr, len);
}

function readString(memory: WebAssembly.Memory, ptr: number, len: number): string {
  return textDecoder.decode(readBytes(memory, ptr, len));
}

function writeString(exports: NextWasmExports, value: string): { ptr: number; len: number } {
  const bytes = textEncoder.encode(value);
  const ptr = exports.alloc(bytes.length);
  const mem = new Uint8Array(exports.memory.buffer, ptr, bytes.length);
  mem.set(bytes);
  return { ptr, len: bytes.length };
}

function normalizePath(path: string): string {
  if (path.startsWith("/")) return path;
  if (path.startsWith("./")) return `/${path.slice(2)}`;
  return `/${path}`;
}

function injectWpData(
  html: string,
  wpDataJson: string | null,
  initialPath = "/",
): string {
  const locationFix = `\n<script>(function(){try{if(location.protocol==='blob:'){history.replaceState({},'', ${JSON.stringify(
    initialPath,
  )});}}catch(e){}})();</script>\n`;

  const injectAtHeadStart = (content: string) => {
    if (html.includes("<head>")) {
      return html.replace("<head>", `<head>${content}`);
    }
    return null;
  };

  if (!wpDataJson) {
    const headInjected = injectAtHeadStart(locationFix);
    if (headInjected) return headInjected;

    if (html.includes("</body>")) {
      return html.replace("</body>", `${locationFix}</body>`);
    }

    return html + locationFix;
  }

  let safeJson = wpDataJson;
  try {
    safeJson = JSON.stringify(JSON.parse(wpDataJson));
  } catch {
    safeJson = JSON.stringify({ raw: wpDataJson });
  }

  const injection = `\n<script>window.__OCTARINE_WP_DATA__=${safeJson};window.dispatchEvent(new Event('octarine:wp-data'));</script>\n${locationFix}`;

  const headInjected = injectAtHeadStart(injection);
  if (headInjected) return headInjected;

  if (html.includes("</body>")) {
    return html.replace("</body>", `${injection}</body>`);
  }

  return html + injection;
}

function rewriteAssetUrls(html: string, vfsMap: Map<string, string>): string {
  const parser = new DOMParser();
  const doc = parser.parseFromString(html, "text/html");

  const nodes = doc.querySelectorAll("[src], [href]");
  nodes.forEach((node) => {
    const element = node as HTMLElement;
    const src = element.getAttribute("src");
    const href = element.getAttribute("href");

    if (src) {
      const normalized = normalizePath(src);
      const mapped = vfsMap.get(normalized);
      if (mapped) element.setAttribute("src", mapped);
    }

    if (href) {
      const normalized = normalizePath(href);
      const mapped = vfsMap.get(normalized);
      if (mapped) element.setAttribute("href", mapped);
    }
  });

  return "<!doctype html>" + doc.documentElement.outerHTML;
}

function injectVfsShim(
  html: string,
  vfsMap: Map<string, string>,
  initialPath: string,
): string {
  const vfsObject: Record<string, string> = {};
  vfsMap.forEach((value, key) => {
    vfsObject[key] = value;
  });

  const shim = `\n<script>(function(){
    try {
      var VFS = ${JSON.stringify(vfsObject)};
      window.__OCTARINE_VFS__ = VFS;
      function normalize(path){
        if(!path) return path;
        if(path.indexOf('#')>=0) path = path.split('#')[0];
        if(path.indexOf('?')>=0) path = path.split('?')[0];
        if(path.length > 1 && path.endsWith('/')) path = path.slice(0, -1);
        if(!path.startsWith('/')) return path;
        return path;
      }
      function resolveVfsUrl(input){
        try {
          if (typeof input === 'string' && input.startsWith('/')) {
            var directPath = normalize(input);
            if (VFS[directPath]) return VFS[directPath];
            if (VFS[directPath + '/index.html']) return VFS[directPath + '/index.html'];
            if (VFS[directPath + '.html']) return VFS[directPath + '.html'];
            return null;
          }

          var url = new URL(input, 'http://octarine.local');
          var path = normalize(url.pathname);
          if (VFS[path]) return VFS[path];
          if (VFS[path + '/index.html']) return VFS[path + '/index.html'];
          if (VFS[path + '.html']) return VFS[path + '.html'];
          return null;
        } catch(e){
          return null;
        }
      }

      function getPathFromUrl(input){
        try {
          if (typeof input === 'string' && input.startsWith('/')) {
            return normalize(input);
          }
          var url = new URL(input, location.href);
          return normalize(url.pathname);
        } catch(e) {
          return null;
        }
      }
      var originalFetch = window.fetch;
      window.fetch = function(input, init){
        var url = (typeof input === 'string') ? input : (input && input.url);
        var mapped = resolveVfsUrl(url);
        return originalFetch(mapped || input, init);
      };

      var originalAppend = Element.prototype.appendChild;
      Element.prototype.appendChild = function(child){
        try {
          if (child && child.tagName) {
            if (child.tagName === 'SCRIPT' && child.src) {
              var mappedScript = resolveVfsUrl(child.src);
              if (mappedScript) child.src = mappedScript;
            }
            if (child.tagName === 'LINK' && child.href) {
              var mappedLink = resolveVfsUrl(child.href);
              if (mappedLink) child.href = mappedLink;
            }
          }
        } catch(e) {}
        return originalAppend.call(this, child);
      };

      document.addEventListener('click', function(event){
        try {
          var target = event.target;
          while (target && target.tagName !== 'A') {
            target = target.parentElement;
          }
          if (!target || !target.getAttribute) return;
          var href = target.getAttribute('href');
          if (!href || href.startsWith('#') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
          if (href.startsWith('http')) return;

          if (location.protocol === 'blob:' && href.startsWith('/')) {
            var mapped = resolveVfsUrl(href);
            if (mapped) {
              event.preventDefault();
              if (event.stopImmediatePropagation) event.stopImmediatePropagation();
              window.location.href = mapped;
            }
            return;
          }

          var mapped = resolveVfsUrl(href);
          if (mapped) {
            event.preventDefault();
            if (event.stopImmediatePropagation) event.stopImmediatePropagation();
            window.location.href = mapped;
          }
        } catch(e) {}
      }, true);

      function navigateTo(path){
        var mapped = resolveVfsUrl(path);
        if (mapped) {
          if (mapped === location.href) return true;
          window.location.href = mapped;
          return true;
        }
        return false;
      }

      try {
        try {
          var protoAssign = Location.prototype.assign;
          Location.prototype.assign = function(url){
            if (!url || url === '#') return;
            if (location.protocol === 'blob:' && typeof url === 'string') {
              var path = getPathFromUrl(url);
              if (path && navigateTo(path)) return;
              return;
            }
            return protoAssign.call(this, url);
          };

          var protoReplace = Location.prototype.replace;
          Location.prototype.replace = function(url){
            if (!url || url === '#') return;
            if (location.protocol === 'blob:' && typeof url === 'string') {
              var path = getPathFromUrl(url);
              if (path && navigateTo(path)) return;
              return;
            }
            return protoReplace.call(this, url);
          };
        } catch(e) {}

        var originalAssign = location.assign.bind(location);
        location.assign = function(url){
          if (!url || url === '#') return;
          if (location.protocol === 'blob:' && typeof url === 'string') {
            var path = getPathFromUrl(url);
            if (path && navigateTo(path)) return;
            return;
          }
          return originalAssign(url);
        };

        var originalReplace = location.replace.bind(location);
        location.replace = function(url){
          if (!url || url === '#') return;
          if (location.protocol === 'blob:' && typeof url === 'string') {
            var path = getPathFromUrl(url);
            if (path && navigateTo(path)) return;
            return;
          }
          return originalReplace(url);
        };

        var hrefDesc = Object.getOwnPropertyDescriptor(Location.prototype, 'href');
        if (hrefDesc && hrefDesc.set && hrefDesc.get) {
          Object.defineProperty(Location.prototype, 'href', {
            configurable: true,
            enumerable: true,
            get: hrefDesc.get,
            set: function(url){
              if (!url || url === '#') return;
              if (location.protocol === 'blob:' && typeof url === 'string') {
                var path = getPathFromUrl(url);
                if (path && navigateTo(path)) return;
              }
              return hrefDesc.set.call(this, url);
            }
          });
        }

        try {
          var locDesc = Object.getOwnPropertyDescriptor(location, 'href');
          if (locDesc && locDesc.set && locDesc.get) {
            Object.defineProperty(location, 'href', {
              configurable: true,
              enumerable: true,
              get: locDesc.get,
              set: function(url){
                if (!url || url === '#') return;
                if (location.protocol === 'blob:' && typeof url === 'string') {
                  var path = getPathFromUrl(url);
                  if (path && navigateTo(path)) return;
                }
                return locDesc.set.call(this, url);
              }
            });
          }
        } catch(e) {}
      } catch(e) {}

      var originalPushState = history.pushState;
      history.pushState = function(state, title, url){
        try {
          if (location.protocol === 'blob:' && typeof url === 'string') {
            try {
              return originalPushState.call(this, state, title, url);
            } catch(e) {
              var path = getPathFromUrl(url);
              var mapped = path ? resolveVfsUrl(path) : null;
              if (mapped) return originalPushState.call(this, state, title, mapped);
              return;
            }
          }
        } catch(e) {
          return;
        }
        return originalPushState.apply(this, arguments);
      };

      var originalReplaceState = history.replaceState;
      history.replaceState = function(state, title, url){
        try {
          if (location.protocol === 'blob:' && typeof url === 'string') {
            try {
              return originalReplaceState.call(this, state, title, url);
            } catch(e) {
              var path = getPathFromUrl(url);
              var mapped = path ? resolveVfsUrl(path) : null;
              if (mapped) return originalReplaceState.call(this, state, title, mapped);
              return;
            }
          }
        } catch(e) {
          return;
        }
        return originalReplaceState.apply(this, arguments);
      };

      if (location.protocol === 'blob:') {
        history.replaceState({}, '', ${JSON.stringify(initialPath)});
      }
    } catch(e) {}
  })();</script>\n`;

  if (html.includes("<head>")) {
    return html.replace("<head>", `<head>${shim}`);
  }

  if (html.includes("</body>")) {
    return html.replace("</body>", `${shim}</body>`);
  }

  return html + shim;
}

export async function loadNcmazFaustWasm(options: NextWasmLoaderOptions) {
  const {
    wasmUrl,
    container,
    wpData,
    wpDataProvider,
    mountMode = "iframe",
    onError,
  } = options;

  try {
    const cacheBustUrl = `${wasmUrl}${wasmUrl.includes("?") ? "&" : "?"}v=${Date.now()}`;
    const response = await fetch(cacheBustUrl, { cache: "no-store" });
    if (!response.ok) {
      throw new Error(`Failed to fetch WASM: ${response.statusText}`);
    }

    const wasmBytes = await response.arrayBuffer();
    const wasmModule = await WebAssembly.instantiate(wasmBytes, {});
    const exports = wasmModule.instance.exports as NextWasmExports;

    if (!exports.memory || !exports.get_file || !exports.get_file_count) {
      throw new Error("WASM module missing expected exports");
    }

    const resolvedWpData =
      wpData !== undefined && wpData !== null
        ? wpData
        : wpDataProvider
          ? await wpDataProvider()
          : null;

    if (resolvedWpData !== undefined && resolvedWpData !== null) {
      const wpJson = JSON.stringify(resolvedWpData);
      const { ptr, len } = writeString(exports, wpJson);
      exports.receive_wp_data(ptr, len);
      exports.dealloc(ptr, len);
    }

    const fileCount = exports.get_file_count();
    const vfsMap = new Map<string, string>();
    const htmlEntries: Array<{ path: string; html: string; mime: string }> = [];

    let wpDataJson: string | null = null;
    if (exports.get_wp_data_len) {
      const wpPtr = exports.get_wp_data_ptr();
      const wpLen = exports.get_wp_data_len();
      if (wpPtr && wpLen) {
        wpDataJson = readString(exports.memory, wpPtr, wpLen);
      }
    }

    for (let i = 0; i < fileCount; i++) {
      const pathPtr = exports.get_file_path_ptr(i);
      const pathLen = exports.get_file_path_len(i);
      const path = readString(exports.memory, pathPtr, pathLen);

      const { ptr, len } = writeString(exports, path);
      const found = exports.get_file(ptr, len) === 1;
      exports.dealloc(ptr, len);
      if (!found) continue;

      const filePtr = exports.get_last_file_ptr();
      const fileLen = exports.get_last_file_len();
      const mimePtr = exports.get_last_file_mime_ptr();
      const mimeLen = exports.get_last_file_mime_len();

      const bytes = readBytes(exports.memory, filePtr, fileLen);
      const mime =
        readString(exports.memory, mimePtr, mimeLen) || "application/octet-stream";

      if (path.endsWith(".html")) {
        htmlEntries.push({
          path,
          html: textDecoder.decode(bytes),
          mime,
        });
        continue;
      }

      const blobUrl = URL.createObjectURL(
        new Blob([new Uint8Array(bytes)], { type: mime }),
      );

      vfsMap.set(path, blobUrl);
    }

    if (!htmlEntries.length) {
      throw new Error("index.html not found in embedded Next.js export");
    }

    let indexHtml: string | null = null;
    let postsIndexHtml: string | null = null;

    htmlEntries.forEach((entry) => {
      if (entry.path === "/index.html") indexHtml = entry.html;
      if (entry.path === "/posts/index.html") postsIndexHtml = entry.html;
    });

    const isIndex404 = !!indexHtml

    htmlEntries.forEach((entry) => {
      let initialPath = "/";
      if (entry.path === "/index.html") {
        initialPath = isIndex404 && postsIndexHtml ? "/posts" : "/";
      } else if (entry.path.endsWith("/index.html")) {
        initialPath = entry.path.replace(/\/index\.html$/, "");
      } else {
        initialPath = entry.path.replace(/\.html$/, "");
      }

      const injected = injectWpData(entry.html, wpDataJson, initialPath);
      const withShim = injectVfsShim(injected, vfsMap, initialPath);
      const rewritten = rewriteAssetUrls(withShim, vfsMap);
      const htmlBlob = new Blob([rewritten], { type: entry.mime });
      const htmlUrl = URL.createObjectURL(htmlBlob);
      vfsMap.set(entry.path, htmlUrl);
    });

    const entryPath =
      isIndex404 && vfsMap.get("/posts/index.html")
        ? "/posts/index.html"
        : "/index.html";
    const htmlUrl = vfsMap.get(entryPath) as string;

    const iframe = document.createElement("iframe");
    iframe.src = htmlUrl;
    iframe.style.width = "100%";
    iframe.style.height = "100%";
    iframe.style.border = "0";
    iframe.setAttribute(
      "sandbox",
      "allow-scripts allow-same-origin allow-popups allow-forms",
    );

    if (mountMode === "shadow") {
      const shadow = container.shadowRoot ?? container.attachShadow({ mode: "open" });
      shadow.innerHTML = "";
      shadow.appendChild(iframe);
    } else {
      container.innerHTML = "";
      container.appendChild(iframe);
    }

    return { exports, iframe, vfsMap };
  } catch (err) {
    const message = err instanceof Error ? err.message : String(err);
    if (onError) onError(message);
    throw err;
  }
}
