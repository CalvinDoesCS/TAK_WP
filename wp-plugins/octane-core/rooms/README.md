# Example: Place your compiled .wasm files here

This directory should contain pairs of files:

- `room-name.wasm` - Compiled WebAssembly binary
- `room-name.js` - ES Module loader that uses WebAssembly.instantiateStreaming

## Quick Start

### 1. Add WASM Files

```bash
# Copy your compiled WASM binary
cp path/to/your/room.wasm ./your-room.wasm

# Copy your JS loader
cp path/to/your/loader.js ./your-room.js
```

### 2. Use in WordPress

```
[octane_room name="your-room"]
```

## File Naming Convention

The room name in the shortcode must match the file names (without extension):

- Shortcode: `[octane_room name="demo"]`
- Files: `demo.wasm` + `demo.js`

## See Also

- `example-room.js` - Template for creating new loaders
- `../README.md` - Full plugin documentation
