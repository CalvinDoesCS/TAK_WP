import { Input } from "@/components/Base/Input";
import { ScrollArea } from "@/components/Base/ScrollArea";
import { Separator } from "@/components/Base/Separator";
import {
  CirclePlay,
  Volume2,
  Bluetooth,
  BatteryFull,
  Wifi,
  Search,
  Power,
  Fingerprint,
  ToggleLeft,
  ToggleRight,
  Fullscreen,
  Moon,
  Sun,
} from "lucide-react";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/Base/Popover";
import { toast } from "sonner";
import { useAppsStore, appCategories } from "@/stores/appsStore";
import { useDarkModeStore } from "@/stores/darkModeStore";
import { useRightClickOptionStore } from "@/stores/rightClickOptionStore";
import { useAppSecurityStore } from "@/stores/appSecurityStore";
import React, { useState, useEffect } from "react";
import _ from "lodash";

function Main() {
  const [dateTime, setDateTime] = useState("");
  const [popoverOpen, setPopoverOpen] = useState(false);
  const { setAppSecurity } = useAppSecurityStore();
  const { selectApps, launchApp, bringWindowToFront } = useAppsStore();
  const { darkMode, setDarkMode } = useDarkModeStore();
  const { setRightClickOption, rightClickOption } = useRightClickOptionStore();
  const imageAssets = import.meta.glob<{
    default: string;
  }>("/src/assets/images/icons/*.{jpg,jpeg,png,svg}", { eager: true });

  // Open app
  const openApp = ({ path }: { path: string }) => {
    const app = selectApps().find((app) => app.path == path);

    if (!app || (app && !Object.keys(app.windows).length)) {
      launchApp({
        path,
      });
    } else {
      bringWindowToFront({
        path,
        index: Object.keys(app.windows)[0],
      });
    }

    setPopoverOpen(false);
  };

  const toggleFullscreen = () => {
    const element: HTMLElement = document.documentElement;

    if (document.fullscreenElement !== null) {
      if (document.exitFullscreen) {
        document.exitFullscreen();
      } else {
        console.log("Your browser doesn't support exiting full screen.");
      }
    } else {
      if (element.requestFullscreen) {
        element.requestFullscreen();
      } else {
        console.log("Your browser doesn't support full screen API.");
      }
    }
  };

  useEffect(() => {
    // Function to format the date and time
    const updateDateTime = () => {
      const now = new Date();
      const day = now.toLocaleString("en-us", { weekday: "short" }); // e.g., Wed
      const date = now.getDate(); // e.g., 27
      const month = now.toLocaleString("en-us", { month: "short" }); // e.g., Mar
      const hours = now.getHours().toString().padStart(2, "0"); // e.g., 14 (24-hour format)
      const minutes = now.getMinutes().toString().padStart(2, "0"); // e.g., 20

      // Example format: "Wed 27 Mar 14:20"
      setDateTime(`${day} ${date} ${month} ${hours}:${minutes}`);
    };

    // Update every second to reflect the current time
    const intervalId = setInterval(updateDateTime, 1000);

    // Call it once initially to avoid 1 second delay
    updateDateTime();

    // Clear interval on component unmount
    return () => clearInterval(intervalId);
  }, []);

  useEffect(() => {
    if (darkMode.isActive) {
      document.querySelectorAll("html")[0].classList.add("dark");
    } else {
      document.querySelectorAll("html")[0].classList.remove("dark");
    }
  }, [darkMode]);

  return (
    <div className="fixed inset-x-0 top-0 z-30 hidden text-background/90 dark:text-foreground/90 md:block">
      <div className="relative flex items-center justify-between px-1 h-9">
        <div className="flex items-center gap-2 basis-1/3">
          <div className="flex items-center">
            <div className="px-2.5 py-1.5 rounded cursor-pointer transition hover:bg-background/10 dark:hover:bg-foreground/10 select-none">
              <Search className="w-4 h-4 drop-shadow-[0_3px_2px_rgb(0_0_0)]" />
            </div>
            <div className="px-2.5 py-1.5 text-xs font-medium transition rounded cursor-pointer hover:bg-background/10 select-none [text-shadow:_0px_2px_7px_rgb(0_0_0)]">
              {
                selectApps().find(
                  (app) =>
                    Object.entries(app.windows).find(
                      ([windowId, window]) => window.focus
                    ) !== undefined
                )?.fileName
              }
            </div>
          </div>
        </div>
        <Popover
          open={popoverOpen}
          onOpenChange={(open) => {
            setPopoverOpen(open);
          }}
        >
          <PopoverTrigger asChild>
            <div className="flex items-center px-3.5 py-1.5 text-xs font-medium transition rounded cursor-pointer hover:bg-background/10 dark:hover:bg-foreground/10 data-[state=open]:bg-background/30 dark:data-[state=open]:hover:bg-background/30 [text-shadow:_0px_2px_7px_rgb(0_0_0)]">
              <Fingerprint className="w-4 h-4 mr-2 drop-shadow-[0_3px_2px_rgb(0_0_0)]" />{" "}
              Start
            </div>
          </PopoverTrigger>
          <PopoverContent className="bg-background/[.95] rounded-lg p-0 mt-0.5 w-[28rem] h-[30rem] shadow-[0_0px_50px_-12px_rgb(0_0_0_/_0.85)]">
            <div className="flex w-full h-full">
              <div className="border-r">
                <div className="flex flex-col justify-between h-full p-2">
                  <div className="flex flex-col gap-1">
                    <div className="flex items-center justify-center h-10 transition rounded-md cursor-pointer w-11 hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.06] selected">
                      <CirclePlay className="w-4 h-4" />
                    </div>
                    <div className="flex items-center justify-center h-10 transition rounded-md cursor-pointer w-11 hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.06]">
                      <Volume2 className="w-4 h-4" />
                    </div>
                    <div className="flex items-center justify-center h-10 transition rounded-md cursor-pointer w-11 hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.06]">
                      <Bluetooth className="w-4 h-4" />
                    </div>
                    <div className="flex items-center justify-center h-10 transition rounded-md cursor-pointer w-11 hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.06]">
                      <BatteryFull className="w-4 h-4" />
                    </div>
                    <div className="flex items-center justify-center h-10 transition rounded-md cursor-pointer w-11 hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.06]">
                      <Wifi className="w-4 h-4" />
                    </div>
                  </div>
                  <div className="flex flex-col gap-1">
                    <div className="flex items-center justify-center h-10 transition rounded-md cursor-pointer w-11 hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.06]">
                      <Search className="w-4 h-4" />
                    </div>
                    <div
                      onClick={() => setAppSecurity({ isLoggedIn: false })}
                      className="flex items-center justify-center h-10 transition rounded-md cursor-pointer w-11 hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.06]"
                    >
                      <Power className="w-4 h-4" />
                    </div>
                  </div>
                </div>
              </div>
              <div className="flex flex-col w-full h-full">
                <div className="relative p-4">
                  <Input
                    type="text"
                    className="pl-3.5 pr-9 rounded-lg text-xs focus-visible:ring-transparent focus-visible:ring-offset-0"
                    placeholder="Search applications..."
                  />
                  <Search className="absolute inset-y-0 right-0 w-4 h-4 my-auto mr-7 text-foreground/40" />
                </div>
                <ScrollArea className="w-full h-full">
                  <div className="flex flex-col w-full gap-5 p-4">
                    <div>
                      <div className="text-xs text-foreground/50">
                        Most Used
                      </div>
                      <div className="grid w-full grid-cols-4 mt-2.5 gap-x-4 gap-y-1.5">
                        {_.take(_.shuffle(selectApps()), 5).map(
                          (app, appKey) =>
                            app.file.icon && (
                              <div
                                className="flex flex-col items-center gap-1.5 cursor-pointer hover:bg-foreground/5 rounded-lg p-2 transition-all"
                                onClick={() => openApp(app)}
                                key={appKey}
                              >
                                <div className="flex justify-center cursor-pointer">
                                  <img
                                    src={
                                      imageAssets[
                                        "/src/assets/images/icons/" +
                                          app.file.icon
                                      ].default
                                    }
                                    className="w-11 h-11"
                                  />
                                </div>
                                <div className="w-full px-1 text-xs text-center truncate text-foreground/60">
                                  {app.fileName}
                                </div>
                              </div>
                            )
                        )}
                      </div>
                    </div>
                    <Separator />
                    {appCategories.map((appCategory, appCategoryKey) => {
                      return (
                        <React.Fragment key={appCategoryKey}>
                          <div>
                            <div className="text-xs text-foreground/50">
                              {appCategory}
                            </div>
                            <div className="grid w-full grid-cols-4 mt-2.5 gap-x-4 gap-y-1.5">
                              {selectApps()
                                .filter((app) => app.category == appCategory)
                                .map(
                                  (app, appKey) =>
                                    app.file.icon && (
                                      <div
                                        className="flex flex-col items-center gap-1.5 cursor-pointer hover:bg-foreground/5 rounded-lg p-2 transition-all"
                                        onClick={() => openApp(app)}
                                        key={appKey}
                                      >
                                        <div className="flex justify-center">
                                          <img
                                            src={
                                              imageAssets[
                                                "/src/assets/images/icons/" +
                                                  app.file.icon
                                              ].default
                                            }
                                            className="w-11 h-11"
                                          />
                                        </div>
                                        <div className="w-full px-1 text-xs text-center truncate text-foreground/60">
                                          {app.fileName}
                                        </div>
                                      </div>
                                    )
                                )}
                            </div>
                          </div>
                          {appCategoryKey < appCategories.length - 1 && (
                            <Separator />
                          )}
                        </React.Fragment>
                      );
                    })}
                  </div>
                </ScrollArea>
              </div>
            </div>
          </PopoverContent>
        </Popover>
        <div className="flex items-center justify-end basis-1/3">
          <div className="flex items-center">
            <div
              className="px-2.5 py-1.5 rounded cursor-pointer transition hover:bg-background/10 dark:hover:bg-foreground/10 select-none"
              onClick={() => toggleFullscreen()}
            >
              <Fullscreen className="w-4 h-4 drop-shadow-[0_3px_2px_rgb(0_0_0)]" />
            </div>
            <div
              className="px-2.5 py-1.5 rounded cursor-pointer transition hover:bg-background/10 dark:hover:bg-foreground/10 select-none"
              onClick={() => {
                toast("System Notification", {
                  icon: (
                    <img
                      src={
                        imageAssets["/src/assets/images/icons/guake.svg"]
                          .default
                      }
                    />
                  ),
                  description: rightClickOption.isActive
                    ? "Right-click menu is not active."
                    : "Right-click menu is active.",
                  action: {
                    label: "Dismiss",
                    onClick: () => console.log("Toggle right-click menu."),
                  },
                });

                setRightClickOption({
                  isActive: !rightClickOption.isActive,
                });
              }}
            >
              {!rightClickOption.isActive ? (
                <ToggleLeft className="w-4 h-4 drop-shadow-[0_3px_2px_rgb(0_0_0)]" />
              ) : (
                <ToggleRight className="w-4 h-4 drop-shadow-[0_3px_2px_rgb(0_0_0)]" />
              )}
            </div>
            <div
              className="px-2.5 py-1.5 rounded cursor-pointer transition hover:bg-background/10 dark:hover:bg-foreground/10 select-none"
              onClick={() => {
                toast("System Notification", {
                  icon: (
                    <img
                      src={
                        imageAssets["/src/assets/images/icons/guake.svg"]
                          .default
                      }
                    />
                  ),
                  description: darkMode.isActive
                    ? "Dark mode is not active."
                    : "Dark mode is active.",
                  action: {
                    label: "Dismiss",
                    onClick: () => console.log("Toggle right-click menu."),
                  },
                });

                setDarkMode({
                  isActive: !darkMode.isActive,
                });
              }}
            >
              {!darkMode.isActive ? (
                <Moon className="w-4 h-4 drop-shadow-[0_3px_2px_rgb(0_0_0)]" />
              ) : (
                <Sun className="w-4 h-4 drop-shadow-[0_3px_2px_rgb(0_0_0)]" />
              )}
            </div>
          </div>
          <div className="px-2.5 py-1.5 text-xs transition rounded cursor-pointer hover:bg-background/10 dark:hover:bg-foreground/10 select-none [text-shadow:_0px_2px_7px_rgb(0_0_0)]">
            {dateTime}
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
