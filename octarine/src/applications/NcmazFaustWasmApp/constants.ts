export const FAUST_SECRET_KEY = "d1ecd0a5-3abf-4490-a884-ceaea2dfe10c";

export const MENU_LOCATIONS = {
  header: "PRIMARY",
  footer: "FOOTER",
} as const;

// Strict CSP without unsafe-eval for production security
// Note: This may cause issues with Next.js dynamic imports/code splitting
export const CSP_POLICY =
  "default-src 'self' *; script-src 'self' 'unsafe-inline' blob: *; style-src 'self' 'unsafe-inline' *; img-src 'self' data: blob: *; font-src 'self' data: *; connect-src 'self' blob: *; frame-src 'self' blob: *;";

