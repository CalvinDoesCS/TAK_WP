import { cn } from "@/lib/utils";
import { useContext } from "react";
import { windowContext } from "./windowContext";
import { useAppsStore } from "@/stores/appsStore";

export interface MainProps extends React.ButtonHTMLAttributes<HTMLDivElement> {}

function Main({ className }: MainProps) {
  const { app, window } = useContext(windowContext);
  const { updateWindow, closeWindow } = useAppsStore();

  return (
    <>
      <div
        className={cn(
          "absolute top-0 left-0 flex gap-2 ml-5 mt-4 z-[60]",
          className
        )}
      >
        <div
          data-control-button
          onClick={() => {
            closeWindow({
              path: app.path,
              index: window.index,
            });
          }}
          className="w-3 h-3 rounded-full cursor-pointer bg-rose-500"
        ></div>
        <div
          data-control-button
          onClick={() => {
            updateWindow({
              path: app.path,
              index: window.index,
              properties: {
                minimize: true,
              },
            });
          }}
          className="w-3 h-3 rounded-full cursor-pointer bg-amber-500"
        ></div>
        <div
          data-control-button
          onClick={() => {
            updateWindow({
              path: app.path,
              index: window.index,
              properties: {
                zoom: !window.zoom,
              },
            });
          }}
          className="w-3 h-3 rounded-full cursor-pointer bg-emerald-500"
        ></div>
      </div>
    </>
  );
}

export default Main;
