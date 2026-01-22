import { createContext } from "react";
import { type File } from "@/components/FileSystem/FileUtils/types";

export interface SelectedFile {
  fileName: string;
  file: File;
}

export interface ClipboardContextValue {
  actionType?: "copy" | "cut";
  path: string;
  selectedFiles: SelectedFile[];
}

export interface ClipboardContextInterface {
  clipboard: ClipboardContextValue;
  setClipboard: (value: ClipboardContextValue) => void;
}

const clipboardContext = createContext<ClipboardContextInterface>({
  clipboard: {
    path: "",
    selectedFiles: [],
  },
  setClipboard: () => {},
});

export { clipboardContext };
