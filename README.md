# TAK_WP Enterprise WASM Desktop Platform

An enterprise-grade, offline-first desktop platform that uses WordPress as a provisioning host and WebAssembly (WASM) micro-apps for execution. Octarine provides the UI shell and windowing model.

## Architecture (Current State)

### Core Components

- **WordPress (Host/Provisioning)**
  - Hosts and serves WASM modules
  - Injects session/config metadata at load time
  - **Not** the runtime database for application state

- **WASM Micro-Apps (Execution + Data)**
  - Self-contained applications
  - Local persistence (SQLite / IndexedDB / WASM FS)
  - Offline-first; local state is the source of truth

- **Octarine (React Shell)**
  - Pure UI shell and window manager
  - Opens WordPress-hosted WASM apps
  - Calls exposed WASM APIs
  - **Does not** store application state

## Current Development Phase: Phase 1 (Local-Only Execution)

**In scope now**

- WASM loading and runtime stability
- UI/UX API calls from Octarine to WASM
- Local state persistence across reloads

**Out of scope now**

- SSO and authentication
- Backend sync and shared state
- Cross-device restore

## Future Phase: Sync & Auth (Planned)

- SSO via WordPress (Firebase or Azure Entra ID)
- Stateful API endpoints for sync
- Multi-device restore and conflict resolution

## Runtime Flow

1. User launches an app in Octarine
2. Octarine opens the WordPress-hosted WASM URL
3. WordPress injects session/config metadata
4. WASM initializes local DB and state
5. WASM runs independently; optional future sync

## Data Ownership Rules

**WASM owns**

- Application data
- User state
- Local persistence

**WordPress owns**

- User identity (future)
- Provisioning metadata
- Session tokens (future)

## Constraints (Non-Negotiable)

- Do not couple WASM runtime logic tightly to WordPress APIs
- Assume intermittent connectivity
- Treat sync as additive, not required
- Keep APIs loosely coupled and versioned

## Repository Layout

```
TAK_WP/
├── headless-wordpress/          # WordPress host/provisioning
├── octarine/                    # React shell
├── wasm-apps/                   # WASM micro-apps
│   ├── ncmaz-faust/             # WASM micro-app (planned)
│   └── open_core_bs/            # WASM micro-app (planned)
├── services/auth/               # Auth abstraction (future phase)
└── micro-app-template/          # Micro-app template
```

## Quick Start (Local)

1. **Create shared Docker network**

```
cd /home/calvin/Documents/TAK_WP/headless-wordpress
chmod +x scripts/create-network.sh
./scripts/create-network.sh
```

2. **Start WordPress host**

```
cd /home/calvin/Documents/TAK_WP/headless-wordpress
docker-compose up -d
```

3. **Start Octarine shell**

```
cd /home/calvin/Documents/TAK_WP/octarine
cp .env.example .env
docker-compose up -d
```

4. **Configure WASM CORS (first time)**

```
cd /home/calvin/Documents/TAK_WP/headless-wordpress
chmod +x scripts/configure-wasm-cors.sh
./scripts/configure-wasm-cors.sh
```

## Reference Docs

- Architecture and setup: [README.md](README.md) and [SETUP_SUMMARY.md](SETUP_SUMMARY.md)
- Octarine app model: [OCTARINE_APP_STRUCTURE.md](OCTARINE_APP_STRUCTURE.md)
- WASM pipeline: [WASM_INTEGRATION.md](WASM_INTEGRATION.md)
  ./scripts/create-network.sh

````

### Can't Connect to WordPress

```bash
# Check if both are on same network
docker network inspect octane-network

# Verify container names
docker ps
````

### CORS Errors

```bash
# Re-run CORS configuration
./scripts/configure-wasm-cors.sh

# Check allowed origins in micro-app .env
```

### Port Already in Use

```bash
# Change ports in docker-compose.yml
ports:
  - "3001:3000"  # Use different external port
```

## 📚 Next Steps

1. **Build Wasm Apps**: Compile your Rust/C++/C# code to Wasm
2. **Serve Wasm Files**: Upload to WordPress or serve from micro-app
3. **Implement SSO**: Add Azure AD or OAuth
4. **Add API Management**: Use Azure APIM for advanced routing
5. **Deploy to Production**: Use Azure Container Instances or AKS

## 🔗 Resources

- [WordPress GraphQL Docs](https://www.wpgraphql.com/)
- [WebAssembly MDN](https://developer.mozilla.org/en-US/docs/WebAssembly)
- [Docker Networks](https://docs.docker.com/network/)
- [JWT.io](https://jwt.io/)

---

**Your Octane Desktop architecture is ready! 🎉**
