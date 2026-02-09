export function normalizePath(p: string): string {
  if (!p) return "/";
  return p.startsWith("/") ? p : `/${p}`;
}

export function verifyBlobContent(blob: Blob, path: string): void {
  if (path !== "/index.html") return;

  const reader = new FileReader();
  reader.onload = function (e) {
    const blobContent = e.target?.result as string;
    console.log(`📋 Blob content length: ${blobContent.length}`);
    console.log(
      `📋 Blob has __NEXT_DATA__: ${blobContent.includes("__NEXT_DATA__")}`,
    );
    const match = blobContent.match(
      /<script id="__NEXT_DATA__"[^>]*>(.*?)<\/script>/s,
    );
    if (match) {
      console.log(`✅ __NEXT_DATA__ found in blob, length: ${match[0].length}`);
      try {
        const parsed = JSON.parse(match[1]);
        console.log(`✅ __NEXT_DATA__ JSON is valid in blob`);
        console.log(`Blob __NEXT_DATA__ preview:`, {
          page: parsed.page,
          hasSeedNode: !!parsed.props.__SEED_NODE__,
          hasPageData: !!parsed.props.pageProps.data?.page,
        });
      } catch (e) {
        console.error(`❌ __NEXT_DATA__ JSON is invalid in blob:`, e);
      }
    } else {
      console.error(`❌ __NEXT_DATA__ NOT found in blob content!`);
      console.log(`Blob content preview:`, blobContent.substring(0, 1000));
    }
  };
  reader.readAsText(blob);
}
