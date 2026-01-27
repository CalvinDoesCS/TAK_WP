import React, { useContext, useEffect, useState, useRef } from "react";
import { cn, generateUniqueId } from "@/lib/utils";
import { useFilesStore } from "@/stores/filesStore";
import { useAppsStore } from "@/stores/appsStore";
import { useDroppable, UseDroppableArguments } from "@dnd-kit/core";
import { getNextIndex } from "../FileUtils/getNextIndex";
import { windowContext } from "@/components/Window/windowContext";
import { rightClickMenuContext } from "@/components/RightClickMenu/rightClickMenuContext";
import { clipboardContext } from "../ClipboardProvider/clipboardContext";
import { resetSelectedFiles } from "./resetSelectedFiles";
import { onRightClick } from "./onRightClick";

export interface MainProps
  extends React.ComponentPropsWithoutRef<"div">,
    UseDroppableArguments {
  id: string;
  path: string;
  index?: number;
  onClick?: (e: React.MouseEvent<HTMLDivElement>) => void;
}

function Main({
  id,
  path,
  index,
  data,
  children,
  className,
  ...props
}: MainProps) {
  const uniqueId = useRef(generateUniqueId());

  const windowContextValue = useContext(windowContext);
  const rightClickMenuContextValue = useContext(rightClickMenuContext);
  const clipboardContextValue = useContext(clipboardContext);

  const filesStore = useFilesStore();
  const appsStore = useAppsStore();

  const droppableId = uniqueId.current + "-" + id;
  const [droppableIndex, setDroppableIndex] = useState(index);

  const { setNodeRef } = useDroppable({
    id: droppableId,
    data: {
      path,
      index: droppableIndex,
      scoped: windowContextValue.scoped,
      app: windowContextValue.app,
      ...data,
    },
  });

  useEffect(() => {
    if (index === undefined) {
      setDroppableIndex(getNextIndex(filesStore.selectFile(path).entries));
    }
  }, [filesStore]);

  return (
    <div
      ref={setNodeRef}
      {...props}
      className={cn(["w-full h-full"], className)}
      id={id}
      onClick={(event) => {
        props.onClick && props.onClick(event);

        resetSelectedFiles({
          path,
          filesStoreActions: filesStore,
        });
      }}
      onMouseEnter={() =>
        onRightClick({
          path,
          filesStoreActions: filesStore,
          appsStoreActions: appsStore,
          windowContext: windowContextValue,
          clipboardContext: clipboardContextValue,
          rightClickMenuContext: rightClickMenuContextValue,
        })
      }
    >
      {children}
    </div>
  );
}

export default Main;
