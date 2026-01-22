import { Rnd } from "react-rnd";
import { cn, delay } from "@/lib/utils";
import { useContext, useEffect, useState, useRef } from "react";
import { windowContext } from "./windowContext";
import { useAppsStore } from "@/stores/appsStore";
import { getComputedPosition, getComputedSize } from "./windowUtils";

export interface WindowProps
  extends React.ButtonHTMLAttributes<HTMLDivElement> {
  x: number | string;
  y: number | string;
  width: number | string;
  height: number | string;
  minWidth?: number | string;
  minHeight?: number | string;
  maxWidth?: number | string;
  maxHeight?: number | string;
}

function Window({
  x,
  y,
  width,
  height,
  minWidth = 300,
  minHeight = 300,
  maxWidth,
  maxHeight,
  children,
  className,
}: WindowProps) {
  const { app, window } = useContext(windowContext);
  const rndRef = useRef<Rnd>(null);
  const desktopEl = document.querySelectorAll(".desktop")[0];
  const [zoomTransition, setZoomTransition] = useState(true);
  const { bringWindowToFront, updateApp } = useAppsStore();

  const setFocus = (event?: MouseEvent) => {
    if (
      event &&
      (event?.target as HTMLDivElement).hasAttribute(`data-control-button`)
    ) {
      return;
    }

    bringWindowToFront({
      path: app.path,
      index: window.index,
    });
  };

  const bringToFront = () => {
    bringWindowToFront({
      path: app.path,
      index: window.index,
    });
  };

  const stopLoadingState = async () => {
    await delay(1000);

    updateApp({
      path: app.path,
      properties: {
        loading: false,
      },
    });
  };

  useEffect(() => {
    if (window.zoom) {
      rndRef.current?.updatePosition({
        ...getComputedPosition({
          desktopEl,
          x: "center",
          y: "center",
          width: maxWidth || desktopEl.getBoundingClientRect().width,
          height: maxHeight || desktopEl.getBoundingClientRect().height,
        }),
      });
      rndRef.current?.updateSize({
        ...getComputedSize({
          desktopEl,
          width: maxWidth || desktopEl.getBoundingClientRect().width,
          height: maxHeight || desktopEl.getBoundingClientRect().height,
        }),
      });
    } else {
      rndRef.current?.updatePosition({
        ...getComputedPosition({
          desktopEl,
          x,
          y,
          width,
          height,
        }),
      });
      rndRef.current?.updateSize({
        ...getComputedSize({
          desktopEl,
          width,
          height,
        }),
      });
    }
  }, [window.zoom, width, height, maxWidth, maxHeight, desktopEl, x, y]);

  useEffect(() => {
    stopLoadingState();
    bringToFront();
  }, []);

  return (
    <>
      <Rnd
        ref={rndRef}
        default={{
          ...getComputedPosition({
            desktopEl,
            x,
            y,
            width,
            height,
          }),
          ...getComputedSize({
            desktopEl,
            width,
            height,
          }),
        }}
        minWidth={minWidth}
        minHeight={minHeight}
        maxWidth={maxWidth}
        maxHeight={maxHeight}
        bounds=".window-bounds"
        dragHandleClassName="draggable-handle"
        data-window={app.path}
        className={cn([
          "group rounded-xl border border-background bg-background/[.95] shadow-[0_0px_50px_-12px_rgb(0_0_0_/_0.85)]",
          {
            "transition-[width,height,transform,opacity] duration-300":
              zoomTransition,
          },
          { "!hidden": window.minimize },
          { "focus-state": window.focus },
          { "opacity-[.95]": !window.focus },
          { "opacity-0": app.loading },
          className,
        ])}
        style={{
          zIndex: window.zIndex,
        }}
        onDragStart={() => setZoomTransition(false)}
        onDragStop={() => setZoomTransition(true)}
        onResizeStart={() => setZoomTransition(false)}
        onResizeStop={() => setZoomTransition(true)}
        onMouseDown={(event) => setFocus(event)}
      >
        <div className="@container/window w-full h-full border rounded-xl border-background/[.15] dark:border-foreground/[.15] relative">
          <div className="relative w-full h-full overflow-hidden rounded-lg">
            {children}
          </div>
        </div>
      </Rnd>
    </>
  );
}

export default Window;
