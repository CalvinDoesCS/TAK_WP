import { createContext } from "react";
import { type File } from "@/components/FileSystem/FileUtils/types";

export interface DirectoryListContextValue {
  path: string;
  file: File;
  focus: boolean;
}

export interface DirectoryListContextInterface {
  directoryList: DirectoryListContextValue[];
  setDirectoryList: (value: DirectoryListContextValue[]) => void;
}

const directoryListContext = createContext<DirectoryListContextInterface>({
  directoryList: [],
  setDirectoryList: () => {},
});

export { directoryListContext };
