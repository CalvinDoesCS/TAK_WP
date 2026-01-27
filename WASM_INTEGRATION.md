# WASM Integration Guide

Complete guide to compile Rust to WebAssembly and integrate with TAK ecosystem.

## Architecture Overview

```
┌─────────────────────────────────────────────────────────┐
│                   OCTARINE (React)                       │
│  ┌──────────────────────────────────────────────────┐   │
│  │  Desktop Component                               │   │
│  │  └─ DraggableWindow                              │   │
│  │     └─ WasmApp (loads & runs .wasm)              │   │
│  └──────────────────────────────────────────────────┘   │
└────────────┬──────────────────────────────────────────────┘
             │ Fetch WASM Module
             │ (HTTP GET)
             ▼
┌─────────────────────────────────────────────────────────┐
│         WORDPRESS (Headless CMS)                         │
│  ┌──────────────────────────────────────────────────┐   │
│  │  REST Endpoint: /wp-json/tak/v1/wasm/[module]   │   │
│  │  └─ Serves .wasm files from wp-content/wasm/    │   │
│  └──────────────────────────────────────────────────┘   │
└────────────┬──────────────────────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────────────────────┐
│       WASM-APPS (Rust + wasm-pack)                      │
│  ┌──────────────────────────────────────────────────┐   │
│  │  src/lib.rs          (Rust source)               │   │
│  │  └─ wasm-pack build  (Compiles to .wasm)        │   │
│  │     └─ pkg/          (Generated .wasm/.js)      │   │
│  └──────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────┘
```

## Quick Start

### 1. Install Prerequisites

```bash
# Install Rust
curl --proto '=https' --tlsv1.2 -sSf https://sh.rustup.rs | sh
source $HOME/.cargo/env

# Verify installation
rustc --version
cargo --version
```

### 2. Build WASM Module

```bash
cd /home/calvin/Documents/TAK_WP/wasm-apps

# Make build script executable
chmod +x build.sh

# Build
./build.sh
```

**Output:**

```
🔨 Building WASM modules...
✅ WASM build complete!
📦 Files copied to: /home/calvin/Documents/TAK_WP/headless-wordpress/wordpress/wp-content/wasm

Generated files:
-rw-r--r-- tak_wasm_apps.wasm
-rw-r--r-- tak_wasm_apps.js
-rw-r--r-- tak_wasm_apps.d.ts
```

### 3. Verify Files in WordPress

```bash
ls -la /home/calvin/Documents/TAK_WP/headless-wordpress/wordpress/wp-content/wasm/
```

### 4. Activate WordPress Plugin

In WordPress admin:

1. Go to **Plugins** → **Installed Plugins**
2. Activate **"TAK WASM Endpoint"**
3. Or activate via CLI:

```bash
docker compose exec wordpress wp plugin activate tak-wasm-endpoint
```

### 5. Test WASM Endpoint

```bash
# List available modules
curl http://localhost:8080/wp-json/tak/v1/wasm

# Response:
# {
#   "modules": [
#     {
#       "name": "tak_wasm_apps",
#       "size": 12345,
#       "url": "http://localhost:8080/wp-json/tak/v1/wasm/tak_wasm_apps"
#     }
#   ]
# }
```

### 6. Load WASM App in Octarine

The Octarine app already has:

- ✅ `WasmApp` application - loads & runs WASM modules
- ✅ Registered in system file manager
- ✅ Window management built-in

**To launch:**

1. Log in to Octarine at `http://localhost:5174`
2. Open **File Manager**
3. Click **"WASM App"** icon
4. Window opens and loads WASM module automatically

The WASM App demonstrates available functions and auto-loads from WordPress API.

## Adding New WASM Functions

### 1. Edit `wasm-apps/src/lib.rs`

```rust
use wasm_bindgen::prelude::*;

#[wasm_bindgen]
pub fn my_function(input: &str) -> String {
    // Your Rust code here
    format!("Processed: {}", input.to_uppercase())
}
```

### 2. Rebuild

```bash
cd wasm-apps
./build.sh
```

### 3. Use in Octarine

The `WasmApp` component auto-detects exported functions:

```typescript
// In WasmApp.tsx, the exports are typed
interface WasmExports {
  my_function?: (input: string) => string;
  // ... other functions
}

// Usage in component:
if (exports.my_function) {
  const result = exports.my_function("test");
  console.log(result); // "Processed: TEST"
}
```

## File Structure

```
/home/calvin/Documents/TAK_WP/
├── wasm-apps/                    (Rust source)
│   ├── Cargo.toml
│   ├── src/lib.rs
│   ├── build.sh
│   ├── pkg/                      (Generated: .wasm, .js, .d.ts)
│   └── README.md
│
├── headless-wordpress/wordpress/
│   └── wp-content/
│       ├── wasm/                 (WASM files served via REST)
│       │   ├── tak_wasm_apps.wasm
│       │   ├── tak_wasm_apps.js
│       │   └── tak_wasm_apps.d.ts
│       └── plugins/
│           └── tak-wasm-endpoint.php  (REST endpoint)
│
└── octarine/src/
    ├── applications/
    │   └── WasmApp/              (Application component)
    │       └── index.tsx         (Loads WASM & renders)
    ├── stores/
    │   ├── filesStore/files.ts   (Registers app icon)
    │   └── appsStore/apps.ts     (Registers app state)
    └── App.tsx
```

## REST API Endpoints

### GET `/wp-json/tak/v1/wasm`

List all available WASM modules.

**Response:**

```json
{
  "modules": [
    {
      "name": "tak_wasm_apps",
      "size": 12345,
      "url": "http://localhost:8080/wp-json/tak/v1/wasm/tak_wasm_apps"
    }
  ]
}
```

### GET `/wp-json/tak/v1/wasm/{module_name}`

Download specific WASM module (binary).

**Headers:**

- `Content-Type: application/wasm`
- `Cache-Control: public, max-age=86400`

**Example:**

```bash
curl -o tak_wasm_apps.wasm \
  http://localhost:8080/wp-json/tak/v1/wasm/tak_wasm_apps
```

## Performance Tips

### Optimize WASM Build

Already configured in `Cargo.toml`:

```toml
[profile.release]
opt-level = "z"  # Optimize for size
lto = true       # Link-Time Optimization
```

Typical size: **~50KB gzipped**

### Caching Strategy

WASM modules are cached by browsers for 24 hours:

```
Cache-Control: public, max-age=86400
```

### Load Time

- First load: ~100-200ms (download + instantiate)
- Subsequent loads: Instant (browser cache)
- Function calls: <1ms (native WASM speed)

## Troubleshooting

### "WASM module not found" Error

1. Check build completed:

   ```bash
   ls /home/calvin/Documents/TAK_WP/headless-wordpress/wordpress/wp-content/wasm/
   ```

2. Verify plugin activated:

   ```bash
   docker compose exec wordpress wp plugin list
   ```

3. Test endpoint:
   ```bash
   curl http://localhost:8080/wp-json/tak/v1/wasm
   ```

### "Failed to instantiate WASM" Error

Check browser console for details:

- Network error? Check endpoint URL in WasmApp props
- Parse error? Check WASM binary isn't corrupted
- API incompatibility? Check wasm-bindgen version in Cargo.toml

### Build Fails with "wasm-pack not found"

The build script auto-installs. If it fails:

```bash
curl https://rustwasm.org/wasm-pack/installer/init.sh -sSf | sh
```

## Adding Custom WASM Apps

Create separate Rust crate for specialized modules:

```bash
cd wasm-apps
cargo new --lib custom-app
cd custom-app

# Edit Cargo.toml - copy from ../Cargo.toml
# Edit src/lib.rs with your implementation

# Build
wasm-pack build --target web --release

# Copy to WordPress
cp pkg/*.wasm /path/to/wordpress/wp-content/wasm/
```

Create new application component in Octarine:

```typescript
// src/applications/CustomWasmApp/index.tsx
import { Window, DraggableHandle, ControlButtons } from '@/components/Window';
import WasmApp from '@/applications/WasmApp';  // Import base component

function CustomWasmApp() {
  return (
    <Window>
      <DraggableHandle>Custom WASM Module</DraggableHandle>
      <ControlButtons />

      <div className="flex-1 overflow-auto p-4 bg-gradient-to-br from-slate-900 to-slate-800">
        <WasmApp moduleName="custom_app" />
      </div>
    </Window>
  );
}

export default CustomWasmApp;
```

Then register in `filesStore` and `appsStore` following the [Octarine Application Structure guide](OCTARINE_APP_STRUCTURE.md).

The key difference from the quick example: Each WASM app is a full application window in the Octarine desktop, not just a component.

## Debugging WASM

### Console Logging

From Rust:

```rust
use web_sys::console;

console::log_1(&"Hello from WASM".into());
console::log_1(&format!("Value: {}", x).into());
```

Check browser DevTools Console.

### Memory Inspection

WASM memory available via `exports.memory`:

```typescript
if (exports.memory) {
  console.log("WASM Memory:", exports.memory);
  const buffer = new Uint8Array(exports.memory.buffer);
  console.log("First 100 bytes:", buffer.slice(0, 100));
}
```

## Production Checklist

- [ ] WASM module built and optimized
- [ ] Files copied to WordPress wp-content/wasm/
- [ ] Plugin activated in WordPress
- [ ] REST endpoint tested and accessible
- [ ] CORS headers configured (if serving from different domain)
- [ ] Cache headers set appropriately
- [ ] HTTPS enabled in production
- [ ] WASM module tested in Octarine
- [ ] Error handling implemented
- [ ] Performance monitored

## Next Steps

1. ✅ Rust WASM project created
2. ✅ Build pipeline configured
3. ✅ WordPress REST endpoint added
4. ✅ Octarine components created
5. Build WASM module: `cd wasm-apps && ./build.sh`
6. Activate WordPress plugin
7. Test WASM app in Octarine desktop
8. Create additional WASM modules as needed
9. Implement enterprise Entra ID auth for WASM apps
