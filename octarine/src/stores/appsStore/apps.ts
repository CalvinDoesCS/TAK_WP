import { type File } from "@/components/FileSystem/FileUtils/types";

export type Window = {
  minimize: boolean;
  zoom: boolean;
  focus: boolean;
  zIndex: number;
  openedFilePath?: string;
  openedFile?: File;
  component?: React.ComponentType;
};

export type AppCategories = "Media" | "Images" | "Games";

export type App = {
  pinned: boolean;
  loading: boolean;
  supportedFileExtensions: string[];
  category: AppCategories;
  windows: {
    [key: string]: Window;
  };
};

export const appCategories: AppCategories[] = ["Media", "Images", "Games"];

export type Apps = {
  System: App;
  "System/File Manager": App;
  "System/Calculator": App;
  "System/WASM App": App;
} & {
  [key: string]: App;
};

export const apps: Apps = {
  System: {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[0],
    windows: {},
  },
  "System/File Manager": {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[1],
    windows: {},
  },
  "System/Calculator": {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[2],
    windows: {},
  },
  "System/Settings": {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[0],
    windows: {},
  },
  "System/Calendar": {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[1],
    windows: {},
  },
  "System/Mail": {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[2],
    windows: {},
  },
  "System/Map": {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[0],
    windows: {},
  },
  "System/Documentation": {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[1],
    windows: {},
  },
  "System/Image Viewer": {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[2],
    windows: {},
  },
  "System/Media Player": {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[0],
    windows: {},
  },
  "System/Contacts": {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[1],
    windows: {},
  },
  "System/Camera": {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[2],
    windows: {},
  },
  "System/Notes": {
    pinned: true,
    loading: false,
    supportedFileExtensions: ["txt", "js", "py", "rb", "php", "pdf", "docx"],
    category: appCategories[0],
    windows: {},
  },
  "System/Bug Report": {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[1],
    windows: {},
  },
  "System/Clock": {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[2],
    windows: {},
  },
  "System/WASM App": {
    pinned: true,
    loading: false,
    supportedFileExtensions: [],
    category: appCategories[0],
    windows: {},
  },
};
