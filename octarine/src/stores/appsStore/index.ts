import { create } from "zustand";
import { getFile } from "@/components/FileSystem/FileUtils/getFile";
import { type File } from "@/components/FileSystem/FileUtils/types";
import { generateUniqueId } from "@/lib/utils";
import { useFilesStore } from "../filesStore";
import { getHighestZIndex } from "./utils";
import { type Window, type App, type Apps, apps, appCategories } from "./apps";

export type ComputedApp = App & {
  path: string;
  fileName: string;
  file: File;
};

export type Actions = {
  selectApps: () => ComputedApp[];
  updateApp: (props: { properties: Partial<App>; path: string }) => void;
  updateWindow: (props: {
    properties: Partial<Window>;
    path: string;
    index: string;
  }) => void;
  bringWindowToFront: (props: { path: string; index: string }) => void;
  launchWindow: (props: {
    path: string;
    openedFilePath?: string;
    component?: ({ windowId }: { windowId: string }) => React.ComponentType;
  }) => void;
  closeWindow: (props: { path: string; index: string }) => void;
  launchApp: (props: { path: string }) => void;
};

const useAppsStore = create<{ apps: Apps } & Actions>((set, get) => ({
  apps: apps,
  selectApps: () => {
    return Object.entries(get().apps)
      .map(([appKey, app]) => {
        const { fileName, file } = getFile({
          files: useFilesStore.getState().Root,
          path: appKey,
        }).result;

        // Check if there's opened file
        Object.keys(app.windows).map((windowKey) => {
          const openedFilePath = app.windows[windowKey].openedFilePath;

          if (openedFilePath) {
            const { file } = getFile({
              files: useFilesStore.getState().Root,
              path: openedFilePath,
            }).result;

            app.windows[windowKey].openedFile = file;
          }
        });

        return {
          ...app,
          file,
          fileName,
          path: appKey,
        };
      })
      .filter(
        (app) =>
          app.path == "System" ||
          (app.file &&
            app.file.component.length &&
            (app.pinned || (!app.pinned && Object.keys(app.windows).length)))
      );
  },
  updateApp: ({ path, properties }) =>
    set((state) => {
      const { apps } = state;

      apps[path] = {
        ...apps[path],
        ...properties,
      };

      return {
        ...state,
        apps,
      };
    }),
  bringWindowToFront: ({ path, index }) =>
    set((state) => {
      const { apps } = state;

      Object.keys(get().apps).map((appKey) => {
        Object.keys(apps[appKey].windows).map(
          (windowId) => (apps[appKey].windows[windowId].focus = false)
        );
      });

      apps[path].windows[index] = {
        ...apps[path].windows[index],
        zIndex: getHighestZIndex(apps),
        minimize: false,
        focus: true,
      };

      return {
        ...state,
        apps,
      };
    }),
  updateWindow: ({ path, properties, index }) =>
    set((state) => {
      const { apps } = state;

      apps[path].windows[index] = {
        ...apps[path].windows[index],
        ...properties,
      };

      return {
        ...state,
        apps,
      };
    }),
  launchWindow: ({ path, openedFilePath, component }) =>
    set((state) => {
      const { apps } = state;
      const windowId = generateUniqueId();
      const newPath = apps[path] ? path : "System";

      Object.keys(get().apps).map((appKey) => {
        Object.keys(apps[appKey].windows).map(
          (windowId) => (apps[appKey].windows[windowId].focus = false)
        );
      });

      apps[newPath].windows[windowId] = {
        minimize: false,
        zoom: false,
        focus: false,
        zIndex: 0,
        openedFilePath,
        component: component
          ? component({
              windowId,
            })
          : undefined,
      };

      return {
        ...state,
        apps,
      };
    }),
  closeWindow: ({ path, index }) =>
    set((state) => {
      const { apps } = state;
      const newPath = apps[path] ? path : "System";

      delete apps[newPath].windows[index];

      return {
        ...state,
        apps,
      };
    }),
  launchApp: ({ path }) =>
    set((state) => {
      const { apps } = state;
      let appPath = path;
      let openedFilePath;

      const file = useFilesStore.getState().selectFile(path);

      if (file.defaultOpenWithApp?.file) {
        appPath = file.defaultOpenWithApp.path;
        openedFilePath = path;
      }

      if (apps[appPath]) {
        apps[appPath].loading = !Object.keys(apps[appPath].windows).length;
      } else {
        apps[appPath] = {
          pinned: false,
          loading: true,
          supportedFileExtensions: [],
          category: appCategories[0],
          windows: {},
        };
      }

      state.launchWindow({
        path: appPath,
        openedFilePath,
      });

      return {
        ...state,
        apps,
      };
    }),
}));

export { useAppsStore, appCategories, type Window, type App };
