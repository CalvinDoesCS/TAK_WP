import { useState, useEffect, useRef, useContext } from "react";
import { type File } from "../FileUtils/types";
import { useFilesStore } from "@/stores/filesStore";
import { useAppsStore } from "@/stores/appsStore";
import { windowContext } from "../../Window/windowContext";
import FileRenameConflictDialog from "../FileRenameConflictDialog";

export interface MainProps {
  type?: "thumbnail" | "list";
  fileName: string;
  file: File;
  path: string;
}

function Main({ fileName, file, type = "thumbnail", path }: MainProps) {
  const inputRef = useRef<HTMLTextAreaElement>(null);
  const [inputFileName, setInputFileName] = useState(fileName);
  const { updateFileProperties, selectFile } = useFilesStore();
  const { launchWindow, closeWindow } = useAppsStore();
  const windowContextValue = useContext(windowContext);

  const cancelEditMode = ({
    path,
    fileName,
  }: {
    path: string;
    fileName: string;
  }) => {
    updateFileProperties({
      path: path,
      properties: {
        editable: false,
      },
      name: fileName,
    });
  };

  useEffect(() => {
    if (file.editable) {
      // Set file name
      setInputFileName(fileName);

      // Set input focus
      setTimeout(() => {
        if (inputRef.current) {
          inputRef.current.focus();
          inputRef.current.setSelectionRange(0, inputFileName.length);
          inputRef.current.scrollTop = inputRef.current.scrollHeight;
        }
      }, 100);
    }
  }, [file.editable]);

  useEffect(() => {
    const handleKeyDown = (event: globalThis.KeyboardEvent) => {
      const isExist =
        inputFileName != fileName && selectFile(path).entries[inputFileName];

      // Apply update
      if (event.key === "Enter" && file.editable) {
        updateFileProperties({
          path: path,
          properties: {
            name: !isExist ? inputFileName : fileName,
            editable: false,
          },
          name: fileName,
        });

        if (isExist) {
          launchWindow({
            path: windowContextValue.app.path,
            component:
              ({ windowId }) =>
              () =>
                (
                  <FileRenameConflictDialog
                    fileName={inputFileName}
                    confirm={() =>
                      closeWindow({
                        path: windowContextValue.app.path,
                        index: windowId,
                      })
                    }
                  />
                ),
          });
        }
      }
    };

    window.addEventListener("keydown", handleKeyDown);

    return () => {
      window.removeEventListener("keydown", handleKeyDown);
    };
  }, [file.selected, file.editable, inputFileName]);

  return type == "thumbnail" ? (
    <textarea
      ref={inputRef}
      rows={Math.floor(inputFileName.length / 15) + 1}
      value={inputFileName}
      onChange={(e) => setInputFileName(e.target.value)}
      onBlur={() => cancelEditMode({ path, fileName })}
      className="w-full text-xs text-center max-h-11 rounded outline-none resize-none bg-background/40 leading-[1.2] py-0.5 selection:bg-blue-600/80 selection:text-white"
    ></textarea>
  ) : (
    <textarea
      ref={inputRef}
      rows={1}
      value={inputFileName}
      onChange={(e) => setInputFileName(e.target.value)}
      onBlur={() => cancelEditMode({ path, fileName })}
      className="ml-2 -mt-px px-1 w-full rounded outline-none resize-none bg-background/40 leading-[1.2] py-0.5 selection:bg-blue-600/80 selection:text-white"
    ></textarea>
  );
}

export default Main;
