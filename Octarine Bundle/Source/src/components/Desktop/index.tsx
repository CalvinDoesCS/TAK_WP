import "@/assets/css/app.css";
import { cn } from "@/lib/utils";
import { useRef, useState, useEffect } from "react";
import { useFilesStore } from "@/stores/filesStore";
import { useAppsStore } from "@/stores/appsStore";
import { FileDraggable, FileDroppable } from "@/components/FileSystem";

function Main() {
  const rootPath = "Desktop";
  const { selectFile } = useFilesStore();
  const { selectApps, updateWindow } = useAppsStore();

  const [desktopSize, setDesktopSize] = useState({ width: 0, height: 0 });
  const desktopRef = useRef<HTMLDivElement>(null);

  const getFileByIndex = (index: number) => {
    return Object.entries(selectFile(rootPath).entries).find(
      ([fileName, file]) => file.index == index
    );
  };

  const setDesktopFocus = () => {
    // Unfocus all windows
    selectApps().map((app) => {
      Object.entries(app.windows).map(([windowId]) => {
        updateWindow({
          path: app.path,
          index: windowId,
          properties: {
            focus: false,
          },
        });
      });
    });
  };

  useEffect(() => {
    const handleResize = () => {
      const divWidth = desktopRef.current?.clientWidth || 0;
      const divHeight = desktopRef.current?.clientHeight || 0;

      setDesktopSize({
        width: Math.max(Math.floor(divWidth / 100), 0),
        height: Math.max(Math.floor(divHeight / 100), 0),
      });
    };

    window.addEventListener("resize", handleResize);
    handleResize();

    return () => {
      window.removeEventListener("resize", handleResize);
    };
  }, []);

  return (
    <>
      <div
        className={cn([
          "desktop fixed inset-0 mb-[4rem] md:mt-9 select-none group",
          {
            "focus-state": !selectApps().find((app) =>
              Object.entries(app.windows).find(
                ([windowId, window]) => window.focus
              )
            ),
          },
        ])}
        onMouseDown={() => setDesktopFocus()}
      >
        <div ref={desktopRef} className="flex py-3.5 h-full w-full">
          {[...Array(desktopSize.width).keys()].map((x) => (
            <div key={x} className="flex flex-col w-full h-full">
              {[...Array(desktopSize.height).keys()].map((y) => {
                const fileBoxIndex =
                  window.innerWidth <= 768
                    ? y * desktopSize.width + x
                    : x * desktopSize.height + y;
                const file = getFileByIndex(fileBoxIndex);
                return (
                  <div
                    key={fileBoxIndex}
                    className="relative flex items-center justify-center w-full h-full"
                  >
                    {file ? (
                      <FileDraggable
                        fileName={file[0]}
                        file={file[1]}
                        path={rootPath}
                      />
                    ) : (
                      <FileDroppable
                        id={fileBoxIndex.toString()}
                        path={rootPath}
                        index={fileBoxIndex}
                        className="w-full h-full"
                      />
                    )}
                  </div>
                );
              })}
            </div>
          ))}
        </div>
      </div>
    </>
  );
}

export default Main;
