import { useEffect } from "react";
import {
  type ComputedApp,
  type Window,
  useAppsStore,
} from "@/stores/appsStore";
import { windowContext } from "@/components/Window/windowContext";
import { useState, useMemo } from "react";

interface Windows {
  app: ComputedApp;
  window: Window & { windowId: string };
  component: React.ComponentType;
}

function App() {
  const appsStore = useAppsStore();
  const apps = useMemo(() => appsStore.selectApps(), [appsStore]);
  const [windows, setWindows] = useState<Windows[]>([]);

  useEffect(() => {
    (async () => {
      const loadedWindows: Windows[] = [];
      const loadedComponents = await Promise.all(
        apps
          .filter((app) => Object.keys(app.windows).length)
          .map(async (app) => {
            return {
              app,
              component:
                app.path != "System"
                  ? (await import(`@/applications/${app.file.component}`))
                      .default
                  : () => {},
            };
          })
      );

      loadedComponents.map((loadedComponent) =>
        Object.entries(loadedComponent.app.windows).map(([windowId, window]) =>
          loadedWindows.push({
            app: loadedComponent.app,
            component: window.component ?? loadedComponent.component,
            window: {
              ...window,
              windowId,
            },
          })
        )
      );

      setWindows(loadedWindows);
    })();
  }, [apps]);

  return (
    <>
      <div className="window-bounds fixed inset-x-0 md:-inset-x-[100%] bottom-[4rem] md:-bottom-[100%] top-0 md:top-[36px] z-[-9999999]"></div>
      {windows.map(({ component: Component, app, window }) => {
        return (
          <windowContext.Provider
            key={window.windowId}
            value={{
              scoped: true,
              app,
              window: {
                index: window.windowId,
                ...window,
              },
            }}
          >
            <Component />
          </windowContext.Provider>
        );
      })}
    </>
  );
}

export default App;
