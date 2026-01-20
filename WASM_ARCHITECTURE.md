# WebAssembly Micro-Frontend Architecture

## Overview

This setup uses WebAssembly modules for ultra-fast, sandboxed micro-apps loaded
dynamically by the NCMAZ shell application.

## Architecture

```
WordPress (API + Auth)
    ↓
NCMAZ Shell (Module Loader)
    ↓
┌─────────────────────────────────────────────┐
│  Wasm Runtime (Browser)                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  │
│  │ Octarine │  │OpenCore  │  │  WPLMS   │  │
│  │  .wasm   │  │  .wasm   │  │  .wasm   │  │
│  └──────────┘  └──────────┘  └──────────┘  │
└─────────────────────────────────────────────┘
```

## Benefits

- **Fast**: Near-native performance
- **Sandboxed**: Each app runs isolated
- **Efficient**: Small bundle sizes
- **Portable**: Run anywhere Wasm is supported

## Tech Stack

- **Rust/AssemblyScript**: For Wasm compilation
- **wasm-bindgen**: JS/Wasm interop
- **Module Federation**: Runtime loading
- **WordPress**: Central auth & data

## Implementation Phases

### Phase 1: Shell Setup ✓

- NCMAZ as container app
- Wasm loader implementation
- Communication bridge

### Phase 2: Compile Micro Apps

- Build Octarine → Wasm
- Build Open Core components → Wasm
- Build WPLMS views → Wasm

### Phase 3: Integration

- WordPress JWT auth
- Shared state management
- Inter-module communication

### Phase 4: Optimization

- Lazy loading
- Caching strategy
- Progressive enhancement
