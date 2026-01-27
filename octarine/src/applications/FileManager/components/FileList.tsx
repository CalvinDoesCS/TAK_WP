import React, { useContext } from "react";
import { ResizableHandle, ResizablePanel } from "@/components/Base/Resizable";
import { generateUniqueId } from "@/lib/utils";
import { useFilesStore } from "@/stores/filesStore";
import { ScrollArea } from "@/components/Base/ScrollArea";
import { FileDraggable, FileDroppable } from "@/components/FileSystem";
import { directoryListContext } from "../context/directoryListContext";
import { fileDetailsContext } from "../context/fileDetailsContext";

function Main({
  fileManagerPanelRef,
}: {
  fileManagerPanelRef: HTMLDivElement | null;
}) {
  const { directoryList, setDirectoryList } = useContext(directoryListContext);
  const { setFileDetails } = useContext(fileDetailsContext);
  const { selectFile, updateFileProperties } = useFilesStore();

  const updateDirectoryList = (currentPath: string) => {
    // Reset all selected files after the current active path
    [
      ...directoryList.filter((directory) => {
        return directory.path.split("/").length > currentPath.split("/").length;
      }),
      ...Object.entries(selectFile(currentPath).entries).map(
        ([fileKey, file]) => ({
          path: file.path + "/" + fileKey,
        })
      ),
    ].map((directory) => {
      updateFileProperties({
        path:
          directory.path.split("/").length > 1
            ? directory.path.split("/").slice(0, -1).join("/")
            : "/",
        properties: {
          selected: false,
        },
        name: directory.path.split("/").slice(-1).join("/"),
      });
    });

    // Add new path directory
    setDirectoryList([
      ...directoryList
        .filter((directory) => {
          return (
            directory.path.split("/").length < currentPath.split("/").length
          );
        })
        .map((directory) => ({
          ...directory,
          focus:
            currentPath.split("/").slice(0, -1).join("/") == directory.path,
        })),
      {
        path: currentPath,
        file: selectFile(currentPath),
        focus: false,
      },
    ]);
  };

  return directoryList.map((directory, directoryKey) => {
    return (
      <React.Fragment key={directoryKey}>
        <ResizablePanel
          className="relative"
          id={directoryKey.toString()}
          order={directoryKey}
        >
          <FileDroppable
            id={generateUniqueId()}
            path={directory.path}
            className="w-full h-full"
            onClick={() => {
              setFileDetails(null);
              updateDirectoryList(directory.path);
            }}
          >
            <ScrollArea className="h-full">
              <div>
                <div className="sticky top-[-1px] px-10 py-2 text-xs border-b text-muted-foreground/80 bg-background z-10">
                  Documents
                </div>
                <div className="flex flex-col gap-px p-2">
                  {Object.entries(directory.file.entries).map(
                    ([fileName, file]) => (
                      <FileDraggable
                        type="list"
                        fileName={fileName}
                        file={file}
                        path={directory.path}
                        key={fileName}
                        displayInput={directory.focus}
                        onClick={() => {
                          updateDirectoryList(file.path + "/" + fileName);

                          if (fileManagerPanelRef) {
                            if (file.type == "directory") {
                              fileManagerPanelRef.scrollLeft =
                                fileManagerPanelRef.scrollWidth;
                            } else {
                              setTimeout(() => {
                                fileManagerPanelRef.scrollLeft =
                                  fileManagerPanelRef.scrollWidth;
                              });
                            }
                          }

                          if (file.type == "directory") {
                            setFileDetails(null);
                          } else {
                            setFileDetails({
                              name: fileName,
                              file,
                            });
                          }
                        }}
                      />
                    )
                  )}
                </div>
              </div>
            </ScrollArea>
          </FileDroppable>
        </ResizablePanel>
        <ResizableHandle />
      </React.Fragment>
    );
  });
}

export default Main;
