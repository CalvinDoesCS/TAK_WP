# Octane Core REST API Endpoints

## 📡 Available Endpoints

### 1. List All Available Rooms

```bash
GET http://localhost:8080/wp-json/octane/v1/rooms
```

**Response:**

```json
{
  "success": true,
  "count": 1,
  "rooms": [
    {
      "name": "demo-room",
      "wasm_url": "http://localhost:8080/wp-content/plugins/octane-core/rooms/demo-room.wasm",
      "js_url": "http://localhost:8080/wp-content/plugins/octane-core/rooms/demo-room.js",
      "wasm_size": 1024,
      "has_loader": true,
      "rest_url": "http://localhost:8080/wp-json/octane/v1/wasm/demo-room"
    }
  ],
  "timestamp": 1234567890
}
```

---

### 2. Get Room Metadata

```bash
GET http://localhost:8080/wp-json/octane/v1/room/{room-name}
```

**Example:**

```bash
curl http://localhost:8080/wp-json/octane/v1/room/demo-room
```

**Response:**

```json
{
  "success": true,
  "room": "demo-room",
  "metadata": {},
  "timestamp": 1234567890
}
```

---

### 3. Download WASM Binary

```bash
GET http://localhost:8080/wp-json/octane/v1/wasm/{room-name}
```

**Example:**

```bash
curl -O http://localhost:8080/wp-json/octane/v1/wasm/demo-room
```

**Headers:**

- `Content-Type: application/wasm`
- `Cache-Control: public, max-age=31536000`
- `Access-Control-Allow-Origin: *`

---

## 🔧 Usage in Frontend

### Using Vite Proxy (Recommended)

In `vite.config.ts`:

```typescript
server: {
  proxy: {
    '/wp-json': {
      target: 'http://localhost:8080',
      changeOrigin: true,
    },
  },
}
```

Then fetch from relative path:

```javascript
// List rooms
const rooms = await fetch("/wp-json/octane/v1/rooms").then((r) => r.json());

// Load WASM
const wasmResponse = await fetch("/wp-json/octane/v1/wasm/demo-room");
const wasmModule = await WebAssembly.instantiateStreaming(wasmResponse);
```

### Direct Fetch (CORS Enabled)

```javascript
const wasmUrl = "http://localhost:8080/wp-json/octane/v1/wasm/demo-room";
const wasmModule = await WebAssembly.instantiateStreaming(fetch(wasmUrl));
```

---

## 📁 Adding WASM Files

### Option 1: Upload via Docker

```bash
# Copy WASM binary
docker cp your-room.wasm headless-wp:/var/www/html/wp-content/plugins/octane-core/rooms/

# Copy JS loader
docker cp your-room.js headless-wp:/var/www/html/wp-content/plugins/octane-core/rooms/

# Verify
curl http://localhost:8080/wp-json/octane/v1/rooms | jq '.rooms'
```

### Option 2: Direct File Copy

```bash
# Copy to mounted volume
cp your-room.wasm /home/calvin/Documents/TAK_WP/headless-wordpress/wordpress/wp-content/plugins/octane-core/rooms/
cp your-room.js /home/calvin/Documents/TAK_WP/headless-wordpress/wordpress/wp-content/plugins/octane-core/rooms/
```

---

## 🧪 Testing the Endpoints

```bash
# 1. List all rooms
curl -s http://localhost:8080/wp-json/octane/v1/rooms | jq .

# 2. Get specific room metadata
curl -s http://localhost:8080/wp-json/octane/v1/room/demo-room | jq .

# 3. Check WASM headers
curl -I http://localhost:8080/wp-json/octane/v1/wasm/demo-room

# 4. Download WASM file
curl -o demo.wasm http://localhost:8080/wp-json/octane/v1/wasm/demo-room
file demo.wasm  # Should show: WebAssembly (wasm) binary module
```

---

## 🎯 Integration with Octarine Desktop

The `WasmApp` component now uses the Octane Core endpoints:

```tsx
// In /octarine/src/applications/WasmApp/index.tsx
const url = `/wp-json/octane/v1/wasm/${moduleName}`;
const wasmModule = await WebAssembly.instantiateStreaming(fetch(url));
```

**Example usage:**

```tsx
<WasmApp moduleName='demo-room' />
```

---

## 🔐 Security Notes

- All endpoints are public (`permission_callback: __return_true`)
- CORS enabled with `Access-Control-Allow-Origin: *`
- Files served with aggressive caching (1 year)
- Only serves files from `/rooms/` directory
- Filename sanitization prevents directory traversal

---

## 📊 Current Status

**Demo Room Available:**

- Name: `demo-room`
- WASM URL: `http://localhost:8080/wp-json/octane/v1/wasm/demo-room`
- REST API: `http://localhost:8080/wp-json/octane/v1/room/demo-room`
- Loader: `demo-room.js` ✅

**Endpoints Active:**

- ✅ `GET /wp-json/octane/v1/rooms` - List all rooms
- ✅ `GET /wp-json/octane/v1/room/{name}` - Get metadata
- ✅ `GET /wp-json/octane/v1/wasm/{name}` - Download WASM binary

---

## 🚀 Next Steps

1. **Compile your WASM module:**

   ```bash
   cargo build --target wasm32-unknown-unknown --release
   ```

2. **Copy to WordPress:**

   ```bash
   docker cp target/wasm32-unknown-unknown/release/your_app.wasm \
     headless-wp:/var/www/html/wp-content/plugins/octane-core/rooms/my-room.wasm
   ```

3. **Use in Octarine:**

   ```tsx
   <WasmApp moduleName='my-room' />
   ```

4. **Or use in WordPress:**
   ```html
   [octane_room name="my-room"]
   ```
