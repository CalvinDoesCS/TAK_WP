import React, { useEffect, useRef, useState, useContext } from "react";
import { createPortal } from "react-dom";
import { cn, generateUniqueId } from "@/lib/utils";
import { File } from "../FileUtils/types";
import { useDraggable } from "@dnd-kit/core";
import { CSS } from "@dnd-kit/utilities";
import { useAppsStore } from "@/stores/appsStore";
import { useFilesStore } from "@/stores/filesStore";
import { windowContext } from "../../Window/windowContext";
import { ctrlKeyContext } from "@/components/FileSystem/CtrlKeyProvider/ctrlKeyContext";
import { rightClickMenuContext } from "@/components/RightClickMenu/rightClickMenuContext";
import { clipboardContext } from "../ClipboardProvider/clipboardContext";
import { openApp } from "./openApp";
import { onRightClick } from "./onRightClick";
import { setSelected } from "./setSelected";
import FileMarkup, { type MainProps as FileMarkupProps } from "../FileMarkup";
import DoubleClick from "@/components/Base/DoubleClick";

export interface MainProps extends React.ComponentPropsWithoutRef<"div"> {
  file: File;
  fileName: string;
  path: string;
  type?: FileMarkupProps["type"];
  onClick?: (e: React.MouseEvent<HTMLDivElement>) => void;
  displayInput?: boolean;
}

function Main({ displayInput = true, ...props }: MainProps) {
  const uniqueId = useRef(generateUniqueId());

  const windowContextValue = useContext(windowContext);
  const ctrlKeyContextValue = useContext(ctrlKeyContext);
  const clipboardContextValue = useContext(clipboardContext);
  const rightClickMenuContextValue = useContext(rightClickMenuContext);

  const filesStore = useFilesStore();
  const appsStore = useAppsStore();

  const draggableRef = useRef<HTMLDivElement | null>(null);
  const [draggablePortalPosition, setDraggablePortalPosition] = useState({
    x: 0,
    y: 0,
  });
  const draggableId = uniqueId.current + "-" + props.file.id;
  const { isDragging, attributes, listeners, setNodeRef, transform } =
    useDraggable({
      id: draggableId,
      data: {
        fileName: props.fileName,
        file: props.file,
        path: props.path,
      },
    });

  // Verify if this draggable component is inside the window and the window is focused
  const isInsideActiveWindow = () =>
    windowContextValue.scoped && windowContextValue.window.focus;

  // Verify if this draggable component is inside the desktop and the desktop is focused
  const isInsideActiveDesktop = () =>
    !windowContextValue.scoped &&
    !appsStore
      .selectApps()
      .find((app) =>
        Object.entries(app.windows).find(([windowId, window]) => window.focus)
      );

  useEffect(() => {
    if (
      draggableRef.current?.getBoundingClientRect().x &&
      draggableRef.current?.getBoundingClientRect().y
    ) {
      setDraggablePortalPosition({
        x: draggableRef.current?.getBoundingClientRect().x,
        y: draggableRef.current?.getBoundingClientRect().y,
      });
    }
  }, [draggableRef, transform]);

  return (
    <div className="relative">
      <div
        className={cn([
          "absolute opacity-50 w-full",
          { "opacity-0": !isDragging },
        ])}
      >
        <FileMarkup
          type={props.type}
          fileName={props.fileName}
          file={props.file}
          path={props.path}
        />
      </div>
      <DoubleClick
        data-file-id={props.file.id}
        className={cn([
          "relative touch-none outline-none",
          { "z-[99999]": isDragging },
          {
            "opacity-0": props.file.animated,
          },
          props.className,
        ])}
        ref={(ref) => {
          setNodeRef(ref);
          draggableRef.current = ref;
        }}
        style={
          transform
            ? {
                transform: CSS.Translate.toString(transform),
              }
            : undefined
        }
        onClick={(event) => {
          props.onClick && props.onClick(event);

          setSelected({
            event,
            file: props.file,
            path: props.path,
            fileName: props.fileName,
            filesStoreActions: filesStore,
            ctrlKeyContext: ctrlKeyContextValue,
          });
        }}
        onDoubleClick={() =>
          openApp({
            path: props.path + "/" + props.fileName,
            fileName: props.fileName,
            filesStoreActions: filesStore,
            appsStoreActions: appsStore,
          })
        }
        onMouseEnter={() =>
          onRightClick({
            path: props.path,
            filesStoreActions: filesStore,
            appsStoreActions: appsStore,
            windowContext: windowContextValue,
            clipboardContext: clipboardContextValue,
            rightClickMenuContext: rightClickMenuContextValue,
          })
        }
        {...listeners}
        {...attributes}
      >
        <div className={cn([{ "opacity-0": isDragging }])}>
          <FileMarkup
            type={props.type}
            fileName={props.fileName}
            file={props.file}
            path={props.path}
            displayInput={
              (isInsideActiveWindow() || isInsideActiveDesktop()) &&
              displayInput
            }
          />
        </div>
        {createPortal(
          <div
            className="fixed z-[99999]"
            style={
              transform
                ? {
                    width: draggableRef.current?.offsetWidth,
                    height: draggableRef.current?.offsetHeight,
                    top: draggablePortalPosition.y,
                    left: draggablePortalPosition.x,
                  }
                : undefined
            }
          >
            <FileMarkup
              type={props.type}
              fileName={props.fileName}
              file={props.file}
              path={props.path}
            />
          </div>,
          document.body
        )}
      </DoubleClick>
    </div>
  );
}

export default Main;
