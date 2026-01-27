# Octane Core WordPress Plugin

**Version:** 1.0.0  
**Purpose:** WordPress glue plugin for Octane Desktop micro-frontend ecosystem with WebAssembly support

## 🎯 Features

- ✅ **WASM MIME Type Support** - Automatic configuration for `.wasm` files
- ✅ **[octane_room] Shortcode** - Embed WASM rooms in any page/post
- ✅ **Identity Bridge** - Extract user from `X-Forwarded-User` header (AWS IAP/Proxy)
- ✅ **WPGraphQL Integration** - Query room metadata via GraphQL
- ✅ **Error Handling** - User-friendly maintenance messages for missing rooms
- ✅ **ES Module Support** - Modern JavaScript with `WebAssembly.instantiateStreaming`

---

## 📁 File Structure

```
octane-core/
├── octane-core.php           # Main plugin entry point
├── inc/
│   └── graphql-resolvers.php # WPGraphQL integration
└── rooms/                    # WASM binaries and loaders
    ├── example-room.js       # Example ES module loader
    └── example-room.wasm     # (Place your WASM files here)
```

---

## 🚀 Installation

### Option 1: Via Docker (Current Setup)

```bash
# Copy plugin to WordPress container
docker cp /home/calvin/Documents/TAK_WP/wp-plugins/octane-core \
  headless-wp:/var/www/html/wp-content/plugins/

# Fix permissions
docker exec -t headless-wp chown -R www-data:www-data \
  /var/www/html/wp-content/plugins/octane-core

# Activate via WP-CLI
docker exec -t headless-wp-cli wp plugin activate octane-core
```

### Option 2: Manual Upload

1. Zip the `octane-core` folder
2. Upload via WordPress Admin → Plugins → Add New → Upload
3. Activate the plugin

---

## 📝 Usage

### Basic Shortcode

Embed a room in any page or post:

```
[octane_room name="example-room"]
```

### With Custom Dimensions

```
[octane_room name="example-room" width="800px" height="600px"]
```

### The `window.OctaneConfig` Object

When the shortcode renders, it injects this global configuration:

```javascript
window.OctaneConfig = {
  identity: "user@example.com", // From X-Forwarded-User or WP user
  roomName: "example-room",
  roomToken: "sha256_hash", // HMAC token for API auth
  wasmUrl: "https://site.com/wp-content/plugins/octane-core/rooms/example-room.wasm",
  apiEndpoint: "https://site.com/wp-json/octane/v1/",
  graphqlEndpoint: "https://site.com/graphql",
  timestamp: 1234567890,
};
```

---

## 🔌 WPGraphQL Integration

### Query Room Data

```graphql
query GetRoomData {
  octaneRoomData(roomName: "example-room")
}
```

**Response:**

```json
{
  "data": {
    "octaneRoomData": "{\"success\":true,\"room\":\"example-room\",\"metadata\":{},\"wasmUrl\":\"...\",\"timestamp\":1234567890}"
  }
}
```

### List Rooms on a Page

```graphql
query GetPage {
  page(id: "123", idType: DATABASE_ID) {
    title
    octaneRooms
  }
}
```

### Update Room Metadata (Mutation)

```graphql
mutation UpdateRoom {
  updateOctaneRoomData(
    input: {
      roomName: "example-room"
      metadata: "{\"version\":\"1.0\",\"permissions\":[\"read\",\"write\"]}"
    }
  ) {
    success
    message
  }
}
```

---

## 🛠️ Creating a New Room

### 1. Build Your WASM Module

Using Rust:

```rust
// src/lib.rs
#[no_mangle]
pub extern "C" fn init() {
    // Initialize room
}

#[no_mangle]
pub extern "C" fn update() {
    // Update room state
}
```

Compile to WASM:

```bash
cargo build --target wasm32-unknown-unknown --release
```

### 2. Create the JS Loader

See `rooms/example-room.js` for a template. Key requirements:

- Must be an ES module
- Use `WebAssembly.instantiateStreaming(fetch(config.wasmUrl), importObject)`
- Access `window.OctaneConfig` for identity and URLs

### 3. Deploy Files

```bash
# Copy to rooms directory
cp target/wasm32-unknown-unknown/release/my_room.wasm \
   /path/to/octane-core/rooms/my-room.wasm

cp my-room-loader.js \
   /path/to/octane-core/rooms/my-room.js
```

### 4. Use the Shortcode

```
[octane_room name="my-room"]
```

---

## 🔒 Identity Bridge

The plugin extracts user identity in this order:

1. **`X-Forwarded-User` header** (AWS IAP, Cloudflare Access, etc.)
2. **WordPress authenticated user** (`wp_get_current_user()`)
3. **Fallback:** `"anonymous"`

### Setting the Header

If using a reverse proxy:

**Nginx:**

```nginx
location / {
    proxy_set_header X-Forwarded-User $remote_user;
    proxy_pass http://wordpress;
}
```

**Apache:**

```apache
RequestHeader set X-Forwarded-User %{REMOTE_USER}e
```

---

## 🔐 Security

### Room Tokens

Each room gets a unique HMAC token:

```php
hash_hmac('sha256', $room_name . '|' . $user_identity . '|' . time(), $secret)
```

**Configure a custom secret** in `wp-config.php`:

```php
define('OCTANE_SECRET_KEY', 'your-256-bit-secret-here');
```

---

## 🐛 Error Handling

### Missing WASM/JS Files

If a room's `.wasm` or `.js` file is not found, the shortcode renders:

```html
<div class="octane-room-maintenance">
  🔧 Room Under Maintenance The example-room room is currently unavailable.
</div>
```

### WASM Load Failures

The JS loader catches errors and displays them in the container:

```javascript
try {
  await WebAssembly.instantiateStreaming(fetch(config.wasmUrl), ...);
} catch (error) {
  showError(error); // Displays user-friendly message
}
```

---

## 🌐 REST API Endpoints

### GET `/wp-json/octane/v1/room/{name}`

Retrieve room metadata:

```bash
curl https://site.com/wp-json/octane/v1/room/example-room
```

**Response:**

```json
{
  "success": true,
  "room": "example-room",
  "metadata": {},
  "timestamp": 1234567890
}
```

---

## 📦 Server Configuration

The plugin automatically adds `.htaccess` rules on activation (Apache):

```apache
# BEGIN Octane WASM
<IfModule mod_mime.c>
    AddType application/wasm .wasm
</IfModule>
<FilesMatch "\.wasm$">
    Header set Content-Type "application/wasm"
    Header set Cache-Control "public, max-age=31536000"
</FilesMatch>
# END Octane WASM
```

### Nginx Configuration

Add to your server block:

```nginx
location ~* \.wasm$ {
    types { application/wasm wasm; }
    add_header Cache-Control "public, max-age=31536000";
}
```

---

## 🧪 Testing the Plugin

### 1. Activate the Plugin

```bash
docker exec -t headless-wp-cli wp plugin activate octane-core
```

### 2. Create a Test Page

```bash
docker exec -t headless-wp-cli wp post create \
  --post_type=page \
  --post_title="WASM Test" \
  --post_content='[octane_room name="example-room"]' \
  --post_status=publish
```

### 3. Check the Output

Visit the page - you should see either:

- The WASM room loading (if `example-room.wasm` exists)
- The maintenance message (if files are missing)

### 4. Test GraphQL

```bash
curl -X POST https://site.com/graphql \
  -H "Content-Type: application/json" \
  -d '{"query":"{ octaneRoomData(roomName: \"example-room\") }"}'
```

---

## 🔄 Upgrade Guide

### To Update Room Binaries

1. Replace `.wasm` and `.js` files in the `rooms/` directory
2. Increment version number in metadata
3. Clear WordPress cache: `wp cache flush`

### To Modify GraphQL Schema

Edit `/inc/graphql-resolvers.php` and refresh GraphQL schema:

```bash
wp graphql flush
```

---

## 🤝 Contributing

### Project Structure

- `octane-core.php` - Core plugin logic, shortcode, REST API
- `inc/graphql-resolvers.php` - WPGraphQL field registration
- `rooms/` - WASM binaries and ES module loaders

### Coding Standards

- Follow WordPress Coding Standards
- Use `sanitize_*` and `esc_*` functions
- All public functions prefixed with `octane_`

---

## � Troubleshooting

### Error: "Invalid command 'Header'" (500 Server Error)

**Symptom:** WordPress returns 500 error, Apache logs show:

```
/var/www/html/.htaccess: Invalid command 'Header'
```

**Cause:** Apache `mod_headers` module not enabled.

**Solution:**

```bash
# Enable the module
docker exec -t headless-wp a2enmod headers

# Restart Apache
docker restart headless-wp
```

The plugin's `.htaccess` rules require `mod_headers` for setting WASM MIME types and cache headers.

### WASM File Not Loading (404)

**Check:**

1. File exists: `wp-content/plugins/octane-core/rooms/your-room.wasm`
2. Permissions: `www-data:www-data` ownership
3. File naming matches shortcode: `[octane_room name="your-room"]` → `your-room.wasm`

### "Room Under Maintenance" Message

**Expected behavior** when:

- `.wasm` file doesn't exist
- `.js` loader file doesn't exist

**To fix:**

```bash
# Verify files exist
docker exec -t headless-wp ls -la /var/www/html/wp-content/plugins/octane-core/rooms/
```

### WPGraphQL Query Returns Null

**Check:**

1. WPGraphQL plugin installed: `wp plugin list | grep wp-graphql`
2. GraphQL endpoint accessible: `curl https://site.com/graphql`
3. Room metadata exists: `wp option get octane_room_your-room`

---

## �📄 License

MIT License - See project root for details

---

## 🆘 Support

For issues or feature requests, contact the Octane Desktop team.

**Plugin Version:** 1.0.0  
**WordPress Compatibility:** 5.8+  
**PHP Requirements:** 7.4+  
**WPGraphQL:** Optional (recommended for headless setups)
