import { createContext } from "react";
import { type ComputedApp, type Window } from "@/stores/appsStore";

export interface WindowContextInterface {
  scoped: boolean;
  app: ComputedApp;
  window: Window & {
    index: string;
  };
}

const windowContext = createContext<WindowContextInterface>({
  scoped: false,
  app: {
    fileName: "",
    path: "",
    pinned: false,
    loading: false,
    supportedFileExtensions: [],
    category: "Media",
    windows: {},
    file: {
      id: "",
      icon: "",
      selected: false,
      animated: false,
      editable: false,
      index: 0,
      type: "directory",
      extension: "",
      component: "",
      entries: {},
    },
  },
  window: {
    index: "",
    minimize: false,
    zoom: false,
    focus: false,
    zIndex: 0,
  },
});

export { windowContext };
