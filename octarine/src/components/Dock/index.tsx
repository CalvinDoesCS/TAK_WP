import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/Base/Tooltip";
import { cn } from "@/lib/utils";
import { useEffect, useState } from "react";
import { useAppsStore, ComputedApp } from "@/stores/appsStore";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
  DropdownMenuSeparator,
} from "@/components/Base/DropdownMenu";
import {
  SquareDot,
  PackageOpen,
  SquareActivity,
  SquareMinus,
  SquarePlus,
  SquareX,
} from "lucide-react";
import DockIcon from "./DockIcon";
import { motion, useMotionValue } from "framer-motion";

function Main() {
  const mouseX = useMotionValue(Infinity);

  const appsStore = useAppsStore();
  const [activeApps, setActiveApps] = useState<
    (ComputedApp & {
      dropdownOpen: boolean;
    })[]
  >([]);

  // Open app
  const openApp = ({ path }: { path: string }) => {
    const app = appsStore.selectApps().find((app) => app.path == path);

    if (!app || (app && !Object.keys(app.windows).length)) {
      appsStore.launchApp({
        path,
      });
    } else {
      appsStore.bringWindowToFront({
        path,
        index: Object.keys(app.windows)[0],
      });
    }
  };

  const setDropdownOpen = ({
    open,
    appKey,
  }: {
    open: boolean;
    appKey: number;
  }) => {
    setActiveApps((prevState) => {
      return prevState.map((item, i) => {
        if (i === appKey) {
          return { ...item, dropdownOpen: open };
        }
        return item;
      });
    });
  };

  useEffect(() => {
    setActiveApps(
      appsStore
        .selectApps()
        .filter((app) => app.path !== "System")
        .map((app) => {
          return {
            ...app,
            dropdownOpen: false,
          };
        })
    );
  }, [appsStore]);

  return (
    <div
      className={cn(
        "z-[999999] fixed inset-x-0 bottom-0 hidden md:flex justify-center",
        {
          hidden: !activeApps.length,
        }
      )}
    >
      <motion.div
        onMouseMove={(e) => mouseX.set(e.pageX)}
        onMouseLeave={() => mouseX.set(Infinity)}
        className="flex items-end justify-center gap-3 px-1.5 pt-1 pb-2 mb-1"
      >
        <TooltipProvider delayDuration={0}>
          {activeApps.map((app, appKey) => (
            <div
              className={cn([
                "relative duration-300",
                { "animate-bounce": app.loading },
              ])}
              onClick={() => !app.dropdownOpen && openApp(app)}
              key={appKey}
            >
              <DropdownMenu
                open={app.dropdownOpen}
                onOpenChange={(open) => {
                  if (!open) {
                    setDropdownOpen({ open: false, appKey });
                  }
                }}
              >
                <DropdownMenuTrigger
                  className="outline-none"
                  onContextMenu={(e) => {
                    e.preventDefault();
                    setDropdownOpen({ open: true, appKey });
                  }}
                >
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <div>
                        <DockIcon icon={app.file.icon} mouseX={mouseX} />
                        {Object.keys(app.windows).length > 0 && (
                          <div className="absolute inset-x-0 bottom-0 w-1 h-1 mx-auto -mb-1.5 bg-white/50 rounded-full"></div>
                        )}
                      </div>
                    </TooltipTrigger>
                    <TooltipContent sideOffset={10}>
                      {app.fileName}
                    </TooltipContent>
                  </Tooltip>
                </DropdownMenuTrigger>
                <DropdownMenuContent
                  sideOffset={10}
                  className="w-48 z-[999999]"
                >
                  {Object.keys(app.windows).length > 0 && (
                    <>
                      {Object.entries(app.windows).map(([windowId, window]) => (
                        <DropdownMenuItem
                          key={windowId}
                          className="gap-3 font-medium"
                          onClick={() =>
                            appsStore.bringWindowToFront({
                              path: app.path,
                              index: windowId,
                            })
                          }
                        >
                          <SquareActivity className="w-4 h-4" />
                          <span>{app.fileName}</span>
                          {window.focus && (
                            <SquareDot className="w-4 h-4 ml-auto" />
                          )}
                        </DropdownMenuItem>
                      ))}
                      <DropdownMenuSeparator />
                    </>
                  )}
                  <DropdownMenuItem
                    className="gap-3"
                    onClick={() =>
                      appsStore.updateApp({
                        path: app.path,
                        properties: {
                          pinned: !app.pinned,
                        },
                      })
                    }
                  >
                    {app.pinned ? (
                      <>
                        <SquareMinus className="w-4 h-4" />
                        <span>Remove from Dock</span>
                      </>
                    ) : (
                      <>
                        <SquarePlus className="w-4 h-4" />
                        <span>Keep in Dock</span>
                      </>
                    )}
                  </DropdownMenuItem>
                  {Object.keys(app.windows).length ? (
                    <>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        className="gap-3"
                        onClick={() =>
                          appsStore.launchApp({
                            path: app.path,
                          })
                        }
                      >
                        <SquarePlus className="w-4 h-4" />
                        <span>New Window</span>
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem
                        className="gap-3"
                        onClick={() =>
                          appsStore.updateApp({
                            path: app.path,
                            properties: {
                              windows: (() => {
                                const windows = app.windows;

                                Object.keys(app.windows).map(
                                  (windowId) =>
                                    (windows[windowId] = {
                                      ...windows[windowId],
                                      minimize: false,
                                    })
                                );

                                return windows;
                              })(),
                            },
                          })
                        }
                      >
                        <SquarePlus className="w-4 h-4" />
                        <span>Show All Windows</span>
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        className="gap-3"
                        onClick={() =>
                          appsStore.updateApp({
                            path: app.path,
                            properties: {
                              windows: (() => {
                                const windows = app.windows;

                                Object.keys(app.windows).map(
                                  (windowId) =>
                                    (windows[windowId] = {
                                      ...windows[windowId],
                                      minimize: true,
                                    })
                                );

                                return windows;
                              })(),
                            },
                          })
                        }
                      >
                        <SquareMinus className="w-4 h-4" />
                        <span>Hide</span>
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        className="gap-3"
                        onClick={() =>
                          appsStore.updateApp({
                            path: app.path,
                            properties: {
                              windows: {},
                            },
                          })
                        }
                      >
                        <SquareX className="w-4 h-4" />
                        Quit
                      </DropdownMenuItem>
                    </>
                  ) : (
                    <>
                      <DropdownMenuItem
                        className="gap-3"
                        onClick={() => openApp(app)}
                      >
                        <PackageOpen className="w-4 h-4" />
                        Open
                      </DropdownMenuItem>
                    </>
                  )}
                </DropdownMenuContent>
              </DropdownMenu>
            </div>
          ))}
        </TooltipProvider>
      </motion.div>
    </div>
  );
}

export default Main;
