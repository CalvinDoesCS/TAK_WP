# Copilot Instructions (TAK_WP)

## Big picture architecture

- This repo is a multi-service workspace: WordPress is the host shell (headless CMS), Octarine is the React desktop UI, and WASM modules are built separately and served by WordPress.
- Data flow: wasm-apps build → files land in [headless-wordpress/wordpress/wp-content/wasm](headless-wordpress/wordpress/wp-content/wasm) → REST endpoint in [headless-wordpress/wordpress/wp-content/plugins/tak-wasm-endpoint.php](headless-wordpress/wordpress/wp-content/plugins/tak-wasm-endpoint.php) serves /wp-json/tak/v1/wasm/{module} → Octarine `WasmApp` loads modules in [octarine/src/applications/WasmApp/index.tsx](octarine/src/applications/WasmApp/index.tsx).
- Octarine’s “desktop OS” model registers apps in two stores and then dynamically loads components (see [OCTARINE_APP_STRUCTURE.md](OCTARINE_APP_STRUCTURE.md)).
- Auth is abstracted via providers in [services/auth](services/auth) with a shared `useAuth` hook and env-configurable provider selection (details in [services/auth/README.md](services/auth/README.md)).

## Key workflows (run from each service directory)

- Create shared Docker network: use scripts in [headless-wordpress/scripts](headless-wordpress/scripts) (see [README.md](README.md) and [headless-wordpress/README.md](headless-wordpress/README.md)).
- Start WordPress: docker-compose up -d in [headless-wordpress](headless-wordpress).
- Start Octarine: docker-compose up -d in [octarine](octarine) (Vite dev server).
- Build WASM: run ./build.sh in [wasm-apps](wasm-apps) (copies output to WordPress wasm folder; see [WASM_INTEGRATION.md](WASM_INTEGRATION.md)).

## Project-specific conventions

- Adding an Octarine app requires **both** stores plus a matching component folder:
  - Register in [octarine/src/stores/filesStore/files.ts](octarine/src/stores/filesStore/files.ts) with `component: "AppName"`.
  - Register in [octarine/src/stores/appsStore/apps.ts](octarine/src/stores/appsStore/apps.ts) under a path like "System/AppName".
  - Component must live at [octarine/src/applications/AppName/index.tsx](octarine/src/applications/AppName/index.tsx) and use `Window`, `DraggableHandle`, `ControlButtons`.
- WASM modules must be named consistently across Rust export, filename, and Octarine module selection (see [WASM_INTEGRATION.md](WASM_INTEGRATION.md)).
- Auth provider selection is env-driven (`VITE_AUTH_PROVIDER`), so avoid hard-coding provider-specific calls in Octarine; use `useAuth` from [services/auth](services/auth).

## Integration points to keep in mind

- WordPress GraphQL and REST endpoints are the main data APIs for frontends (details in [headless-wordpress/README.md](headless-wordpress/README.md)).
- Octarine fetches WASM over HTTP from WordPress; failures usually trace back to the plugin endpoint or missing files in wp-content/wasm.
- The workspace includes a micro-app template for “room” APIs and scoped JWT transitions (see [README.md](README.md)).

## Reference docs

- Architecture & setup: [README.md](README.md), [SETUP_SUMMARY.md](SETUP_SUMMARY.md)
- Octarine app model: [OCTARINE_APP_STRUCTURE.md](OCTARINE_APP_STRUCTURE.md)
- WASM pipeline: [WASM_INTEGRATION.md](WASM_INTEGRATION.md)
