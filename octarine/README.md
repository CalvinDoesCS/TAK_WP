# Octarine (React Shell)

Octarine is the desktop UI shell and window manager for the TAK_WP platform. It launches WordPress-hosted WASM micro-apps and provides a consistent UI/UX layer.

## Responsibilities

- Render the shell UI and windowing system
- Launch WASM micro-apps hosted by WordPress
- Call exposed WASM APIs for UI/UX actions

## Non-Responsibilities

- No application state storage
- No direct ownership of app data
- No tight coupling to WordPress runtime APIs

## Local Development

1. Copy the environment file:

```
cp .env.example .env
```

2. Start the container:

```
docker-compose up -d
```

3. Open the UI:

```
http://localhost:5173
```

## Environment Variables

See [octarine/.env.example](octarine/.env.example) for defaults.

- `VITE_API_URL`: WordPress REST API base (used for non-runtime metadata)
- `VITE_WP_API_BASE`: Base URL for WordPress-hosted WASM modules
- `PORT`: Vite dev server port

## Architecture Notes

- Octarine treats WASM apps as standalone executables once loaded.
- Local state is owned by each WASM app (offline-first).
- Auth and sync are future-phase concerns and must remain optional.
