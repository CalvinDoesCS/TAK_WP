# Octarine Application Structure Guide

## Overview

Octarine follows a desktop OS paradigm where applications are registered in the system and managed by the desktop environment.

## How Applications Work

### 1. **Application Registration**

Applications are registered in two places:

#### A. File System Store (`src/stores/filesStore/files.ts`)

Defines how the app appears in the file manager:

```typescript
"WASM App": {
  id: generateUniqueId(),
  icon: "application-default-icon.svg",
  selected: false,
  animated: false,
  editable: false,
  index: 12,
  type: "file",
  extension: "app",
  component: "WasmApp",  // Path to application component (inside /src/applications/)
  entries: {},
}
```

#### B. Apps Store (`src/stores/appsStore/apps.ts`)

Defines the app's window management state:

```typescript
"System/WASM App": {
  pinned: true,              // Appears in taskbar/dock
  loading: false,
  supportedFileExtensions: [],
  category: appCategories[0],
  windows: {},               // Manages open windows
}
```

### 2. **Application Component**

Create the application in `/src/applications/{AppName}/index.tsx`:

```typescript
// src/applications/WasmApp/index.tsx
import { Window, DraggableHandle, ControlButtons } from '@/components/Window';

function WasmApp() {
  return (
    <Window>
      <DraggableHandle>WASM App Title</DraggableHandle>
      <ControlButtons />
      
      <div className="flex-1 overflow-auto p-4">
        {/* Your app content here */}
      </div>
    </Window>
  );
}

export default WasmApp;
```

**Important**: The component name must match the `component` field in both stores exactly!

- File System: `component: "WasmApp"`
- App Path: `"System/WASM App"`
- Folder: `applications/WasmApp/`
- File: `applications/WasmApp/index.tsx`

### 3. **Application Lifecycle**

When user clicks an app icon:

1. **File System** → User clicks "WASM App" in file manager
2. **Apps Store** → Finds `"System/WASM App"` entry
3. **Application Loader** → Dynamically imports `applications/WasmApp/index.tsx`
4. **Window Manager** → Creates window and renders component
5. **State Management** → Tracks window state (focus, minimize, z-index)

## Current Applications

### System Applications

```
System/
├── File Manager      → FileManager component
├── Calculator        → Calculator component
├── Settings          → Settings component
├── Calendar          → Calendar component
├── Mail              → Mail component
├── Documentation     → Documentation component
├── Notes             → Notes component
├── Bug Report        → BugReport component
├── Clock             → Clock component
└── WASM App          → WasmApp component (NEW)
```

## Adding a New Application

### Step 1: Create Application Folder

```bash
mkdir -p src/applications/MyNewApp
```

### Step 2: Create Component (`index.tsx`)

```typescript
// src/applications/MyNewApp/index.tsx
import { Window, DraggableHandle, ControlButtons } from '@/components/Window';

function MyNewApp() {
  return (
    <Window>
      <DraggableHandle>My New App</DraggableHandle>
      <ControlButtons />
      <div className="flex-1 overflow-auto p-4 bg-gradient-to-br from-slate-900 to-slate-800">
        <p className="text-slate-300">Welcome to My New App</p>
      </div>
    </Window>
  );
}

export default MyNewApp;
```

### Step 3: Register in File System (`filesStore/files.ts`)

Add entry in `System` section:

```typescript
"My New App": {
  id: generateUniqueId(),
  icon: "application-default-icon.svg",
  selected: false,
  animated: false,
  editable: false,
  index: 13,  // Increment index
  type: "file",
  extension: "app",
  component: "MyNewApp",  // Must match folder name
  entries: {},
}
```

### Step 4: Register in Apps Store (`appsStore/apps.ts`)

A. Update type definition:

```typescript
export type Apps = {
  System: App;
  // ... other apps
  "System/My New App": App;  // ADD THIS
} & {
  [key: string]: App;
};
```

B. Add app definition:

```typescript
"System/My New App": {
  pinned: true,
  loading: false,
  supportedFileExtensions: [],
  category: appCategories[0],
  windows: {},
}
```

### Step 5: Done!

The app now appears in the file manager and can be launched.

## Window Components

All apps use these window-related components:

```typescript
import { Window, DraggableHandle, ControlButtons } from '@/components/Window';

// Window           - Main container
// DraggableHandle  - Title bar (draggable)
// ControlButtons   - Minimize/Maximize/Close buttons
```

## Styling

Octarine uses Tailwind CSS. Recommended pattern for app content:

```typescript
<div className="flex-1 overflow-auto p-4 bg-gradient-to-br from-slate-900 to-slate-800">
  {/* Your content */}
</div>
```

## State Management

Apps have access to global stores:

```typescript
import { useAppsStore } from "@/stores/appsStore";
import { useFilesStore } from "@/stores/filesStore";

function MyApp() {
  const appsStore = useAppsStore();
  const filesStore = useFilesStore();
  
  // Use store methods...
}
```

## File Operations

Apps can integrate with the file system:

```typescript
import { getFile } from "@/components/FileSystem/FileUtils/getFile";

function MyApp() {
  const { result } = getFile({
    files: useFilesStore.getState().Root,
    path: "Desktop/myfile.txt"
  });
  
  // Use file result...
}
```

## WasmApp Specifics

The WASM App follows the pattern above with additional WASM loading:

1. **Located at**: `src/applications/WasmApp/index.tsx`
2. **Registered as**: `"System/WASM App"` in both stores
3. **Loads WASM from**: `http://localhost:8080/wp-json/tak/v1/wasm/{moduleName}`
4. **Default module**: `tak_wasm_apps` (configurable via props)

### Using WasmApp

```typescript
// View/run WASM app in Octarine
// 1. Click File Manager
// 2. Click "WASM App" icon
// 3. App launches and loads WASM module
// 4. See demo functions execute
```

### Customizing WASM Module

Edit props in `src/applications/WasmApp/index.tsx`:

```typescript
<WasmApp 
  moduleName="tak_wasm_apps"  // Change to different module
  wasmUrl={undefined}          // Or provide custom URL
/>
```

## Application Entry Points

| Layer | Purpose |
|-------|---------|
| `FileSystem` | User sees and clicks app icons |
| `filesStore` | Defines app metadata and component path |
| `appsStore` | Manages app windows and state |
| `ApplicationLoader` | Dynamically loads component |
| `Application` | Rendered component with UI |

## Summary

```
User clicks app icon
    ↓
FileSystem (File Manager UI)
    ↓
filesStore (knows component path: "WasmApp")
    ↓
appsStore (knows app is "System/WASM App")
    ↓
ApplicationLoader (dynamically imports)
    ↓
src/applications/WasmApp/index.tsx (renders)
    ↓
User sees app window on desktop
```

This architecture allows Octarine to act like a real desktop OS where apps are modular, discoverable, and independently managed.
