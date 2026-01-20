import { useAppsStore, appCategories } from "@/stores/appsStore";
import { useAppSecurityStore } from "@/stores/appSecurityStore";
import { Input } from "@/components/Base/Input";
import { ScrollArea } from "@/components/Base/ScrollArea";
import { Separator } from "@/components/Base/Separator";
import {
  Smartphone,
  LayoutGrid,
  AppWindowMac,
  X,
  Layers3,
  Power,
  Search,
} from "lucide-react";
import { useState } from "react";
import { cn } from "@/lib/utils";
import React from "react";
import _ from "lodash";

function Main() {
  const { selectApps, updateApp, launchApp, bringWindowToFront } =
    useAppsStore();
  const { setAppSecurity } = useAppSecurityStore();
  const [isShowAppLauncher, setIsShowAppLauncher] = useState(false);
  const [isShowAppActive, setIsShowAppActive] = useState(false);

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

    setIsShowAppLauncher(false);
  };

  return (
    <>
      {isShowAppLauncher && (
        <div className="fixed inset-0 mx-1 mt-1 mb-16 rounded-xl bg-muted animate-in fade-in-0 zoom-in-95 z-[99999999]">
          <div className="flex flex-col w-full h-full">
            <div className="relative p-4">
              <Input
                type="text"
                className="py-6 pl-4 pr-10 text-xs rounded-xl placeholder:text-foreground/50 focus-visible:ring-transparent focus-visible:ring-offset-0"
                placeholder="Search applications..."
              />
              <Search className="absolute inset-y-0 right-0 w-4 h-4 my-auto mr-8 text-foreground/40" />
            </div>
            <ScrollArea className="w-full h-full">
              <div className="flex flex-col w-full gap-5 px-4 pt-2 pb-6">
                <div>
                  <div className="text-xs text-foreground/50">Most used</div>
                  <div className="grid w-full grid-cols-4 mt-3 gap-x-5 gap-y-4">
                    {_.take(_.shuffle(selectApps()), 5).map(
                      (app, appKey) =>
                        app.file.icon && (
                          <div
                            className="flex flex-col items-center gap-1.5"
                            key={appKey}
                          >
                            <div
                              className="flex justify-center"
                              onClick={() => openApp(app)}
                            >
                              <img
                                src={
                                  imageAssets[
                                    "/src/assets/images/icons/" + app.file.icon
                                  ].default
                                }
                                className="w-12 h-12"
                              />
                            </div>
                            <div className="w-20 px-1 text-xs text-center truncate text-foreground/60">
                              {app.fileName}
                            </div>
                          </div>
                        )
                    )}
                  </div>
                </div>
                <Separator className="bg-background/20" />
                {appCategories.map((appCategory, appCategoryKey) => {
                  return (
                    <React.Fragment key={appCategoryKey}>
                      <div>
                        <div className="text-xs text-foreground/50">
                          {appCategory}
                        </div>
                        <div className="grid w-full grid-cols-4 mt-3 gap-x-5 gap-y-4">
                          {selectApps()
                            .filter((app) => app.category == appCategory)
                            .map(
                              (app, appKey) =>
                                app.file.icon && (
                                  <div
                                    className="flex flex-col items-center gap-1.5"
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
                                        className="w-12 h-12"
                                      />
                                    </div>
                                    <div className="w-20 px-1 text-xs text-center truncate text-foreground/60">
                                      {app.fileName}
                                    </div>
                                  </div>
                                )
                            )}
                        </div>
                      </div>
                      {appCategoryKey < appCategories.length - 1 && (
                        <Separator className="bg-background/20" />
                      )}
                    </React.Fragment>
                  );
                })}
              </div>
            </ScrollArea>
          </div>
        </div>
      )}
      {isShowAppActive && (
        <div className="fixed inset-0 p-3 mx-1 mt-1 mb-16 rounded-xl bg-muted animate-in fade-in-0 zoom-in-95 z-[99999999]">
          {!selectApps().filter(
            (app) =>
              app.path !== "System" && Object.keys(app.windows).length > 0
          ).length ? (
            <div className="flex flex-col items-center justify-center w-full h-full gap-0.5 text-center text-foreground/50">
              <Layers3 className="w-16 h-16 mb-1 [&.lucide]:stroke-[.5] text-foreground/30 fill-foreground/[.03]" />
              <div>No recent items</div>
            </div>
          ) : (
            <ScrollArea className="w-full h-full rounded-xl">
              <div className="grid grid-cols-2 gap-2">
                {selectApps()
                  .filter((app) => app.path !== "System")
                  .map((app, appKey) => (
                    <React.Fragment key={appKey}>
                      {Object.keys(app.windows).length > 0 &&
                        Object.entries(app.windows).map(([windowId]) => (
                          <div
                            className="relative rounded-xl pt-[120%] bg-background border overflow-hidden"
                            key={windowId}
                            onClick={() => {
                              updateApp({
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
                              });

                              setIsShowAppActive(false);
                            }}
                          >
                            <div className="absolute inset-0 flex flex-col px-2.5 pb-2.5">
                              <div className="flex items-center py-2.5 px-1 gap-1.5">
                                <img
                                  src={
                                    imageAssets[
                                      "/src/assets/images/icons/" +
                                        app.file.icon
                                    ].default
                                  }
                                  className="w-5 h-5"
                                />
                                <div className="text-muted-foreground">
                                  {app.fileName}
                                </div>
                                <a
                                  href=""
                                  className="ml-auto text-muted-foreground"
                                  onClick={(e) => {
                                    e.preventDefault();
                                    updateApp({
                                      path: app.path,
                                      properties: {
                                        windows: {},
                                      },
                                    });
                                  }}
                                >
                                  <X className="w-4 h-4" />
                                </a>
                              </div>
                              <div className="bg-muted h-full rounded-lg border bg-cover bg-[url(/src/assets/images/wallpapers/mountain/background-1.jpg)]"></div>
                            </div>
                          </div>
                        ))}
                    </React.Fragment>
                  ))}
              </div>
            </ScrollArea>
          )}
        </div>
      )}
      <div
        className={cn([
          "fixed inset-x-0 bottom-0 flex items-center justify-center h-16 mx-5 gap-5 text-background/90 dark:text-foreground/90 md:hidden",
          {
            "border-t border-background/20 dark:border-foreground/20":
              !isShowAppLauncher && !isShowAppActive,
          },
        ])}
      >
        <a
          className="flex justify-center flex-1 py-3 rounded-xl"
          href=""
          onClick={(e) => {
            e.preventDefault();

            setIsShowAppActive(false);
            setIsShowAppLauncher(false);

            selectApps().map((app) => {
              updateApp({
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
              });
            });
          }}
        >
          <Smartphone className="w-8 h-8 drop-shadow-[0_3px_2px_rgb(0_0_0)] [&.lucide]:stroke-[.8] [&.lucide]:fill-background/20" />
        </a>
        <a
          className={cn([
            "flex justify-center flex-1 py-3 rounded-xl",
            { "bg-background/40 backdrop-blur": isShowAppLauncher },
          ])}
          href=""
          onClick={(e) => {
            e.preventDefault();
            setIsShowAppLauncher(!isShowAppLauncher);
            setIsShowAppActive(false);
          }}
        >
          <LayoutGrid className="w-8 h-8 drop-shadow-[0_3px_2px_rgb(0_0_0)] [&.lucide]:stroke-[.8] [&.lucide]:fill-background/20" />
        </a>
        <a
          className={cn([
            "flex justify-center flex-1 py-3 rounded-xl",
            { "bg-background/40 backdrop-blur": isShowAppActive },
          ])}
          href=""
          onClick={(e) => {
            e.preventDefault();
            setIsShowAppActive(!isShowAppActive);
            setIsShowAppLauncher(false);
          }}
        >
          <AppWindowMac className="w-8 h-8 drop-shadow-[0_3px_2px_rgb(0_0_0)] [&.lucide]:stroke-[.8] [&.lucide]:fill-background/20" />
        </a>
        <a
          className="flex justify-center flex-1 py-3 rounded-xl"
          href=""
          onClick={(e) => {
            e.preventDefault();
            setAppSecurity({ isLoggedIn: false });
          }}
        >
          <Power className="w-8 h-8 drop-shadow-[0_3px_2px_rgb(0_0_0)] [&.lucide]:stroke-[.8] [&.lucide]:fill-background/20" />
        </a>
      </div>
    </>
  );
}

export default Main;
