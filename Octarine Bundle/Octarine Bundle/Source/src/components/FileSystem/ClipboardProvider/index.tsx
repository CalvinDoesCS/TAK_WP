import { useState } from "react";
import {
  clipboardContext,
  type ClipboardContextValue,
} from "./clipboardContext";

export interface MainProps extends React.PropsWithChildren {}

function Main({ children }: MainProps) {
  const [clipboard, setClipboard] = useState<ClipboardContextValue>({
    actionType: undefined,
    path: "",
    selectedFiles: [],
  });

  return (
    <clipboardContext.Provider value={{ clipboard, setClipboard }}>
      {children}
    </clipboardContext.Provider>
  );
}

export default Main;
