import { createContext } from "react";
import { type FileType } from "@/components/FileSystem/FileUtils/types";

export type FileDetailsContextValue = {
  name: string;
  file: FileType;
} | null;

export interface FileDetailsContextInterface {
  fileDetails: FileDetailsContextValue;
  setFileDetails: (value: FileDetailsContextValue) => void;
}

const fileDetailsContext = createContext<FileDetailsContextInterface>({
  fileDetails: null,
  setFileDetails: () => {},
});

export { fileDetailsContext };
