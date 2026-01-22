import {
  ResizableHandle,
  ResizablePanel,
  ResizablePanelGroup,
} from "@/components/Base/Resizable";
import {
  directoryListContext,
  type DirectoryListContextValue,
} from "./context/directoryListContext";
import {
  fileDetailsContext,
  type FileDetailsContextValue,
} from "./context/fileDetailsContext";
import { useContext, useState, useRef, useEffect } from "react";
import { windowContext } from "@/components/Window/windowContext";
import { Window, DraggableHandle, ControlButtons } from "@/components/Window";
import { SidebarProvider } from "@/components/Base/Sidebar";
import { useFilesStore } from "@/stores/filesStore";
import FileList from "./components/FileList";
import FileDetails from "./components/FileDetails";
import SideMenu from "./components/SideMenu";
import Toolbar from "./components/Toolbar";

function Main() {
  const filesStore = useFilesStore();
  const { window } = useContext(windowContext);
  const [directoryList, setDirectoryList] = useState<
    DirectoryListContextValue[]
  >([]);
  const [fileDetails, setFileDetails] = useState<FileDetailsContextValue>(null);
  const fileManagerPanelRef = useRef<HTMLDivElement>(null);

  const getFilteredDirectoryList = (
    directoryList: DirectoryListContextValue[]
  ) =>
    directoryList.filter(
      (directory) =>
        directory.file &&
        filesStore.selectFile(directory.path) &&
        directory.file.type === "directory"
    );

  const getResizablePanelGroupStyle = (
    directoryList: DirectoryListContextValue[]
  ) => ({
    width: `${
      280 *
      (getFilteredDirectoryList(directoryList).length + (!fileDetails ? 1 : 0))
    }px`,
  });

  useEffect(() => {
    if (!directoryList.length) {
      const defaultPath = window.openedFilePath
        ? window.openedFilePath
        : "Desktop";

      setDirectoryList([
        {
          path: defaultPath,
          file: filesStore.selectFile(defaultPath),
          focus: false,
        },
      ]);
    } else {
      setDirectoryList(
        directoryList.map((directory) => ({
          ...directory,
          file: filesStore.selectFile(directory.path),
        }))
      );
    }
  }, [filesStore]);

  return (
    <Window
      x="center"
      y="center"
      width="70%"
      height="80%"
      maxWidth="90%"
      maxHeight="90%"
    >
      <ControlButtons className="mt-5" />
      <SidebarProvider className="h-full min-h-0">
        <ResizablePanelGroup direction="horizontal">
          <ResizablePanel
            className="hidden @lg/window:block max-w-xs min-w-60 relative"
            defaultSize={18}
            minSize={18}
          >
            <DraggableHandle className="absolute inset-x-0 top-0 w-full h-14" />
            <div className="h-full pt-14">
              <div className="relative h-full">
                <SideMenu
                  directoryList={directoryList}
                  setDirectoryList={setDirectoryList}
                />
              </div>
            </div>
          </ResizablePanel>
          <ResizableHandle withHandle className="hidden @lg/window:flex" />
          <ResizablePanel className="z-50 shadow-xl bg-background/70">
            <div className="flex flex-col h-full">
              <Toolbar />
              <div
                ref={fileManagerPanelRef}
                className="h-full overflow-x-auto overflow-y-hidden scrollbar"
              >
                <div className="flex h-full">
                  <fileDetailsContext.Provider
                    value={{ fileDetails, setFileDetails }}
                  >
                    <ResizablePanelGroup
                      className="flex-none"
                      style={getResizablePanelGroupStyle(directoryList)}
                      direction="horizontal"
                    >
                      <directoryListContext.Provider
                        value={{
                          directoryList:
                            getFilteredDirectoryList(directoryList),
                          setDirectoryList,
                        }}
                      >
                        <FileList
                          fileManagerPanelRef={fileManagerPanelRef.current}
                        />
                      </directoryListContext.Provider>
                      {!fileDetails && (
                        <ResizablePanel
                          id={(directoryList.length + 1).toString()}
                          order={directoryList.length + 1}
                        ></ResizablePanel>
                      )}
                    </ResizablePanelGroup>
                    {fileDetails && <FileDetails />}
                  </fileDetailsContext.Provider>
                </div>
              </div>
            </div>
          </ResizablePanel>
        </ResizablePanelGroup>
      </SidebarProvider>
    </Window>
  );
}

export default Main;
