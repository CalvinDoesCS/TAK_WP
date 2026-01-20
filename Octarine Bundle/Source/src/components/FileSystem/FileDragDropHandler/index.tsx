import { getActionProps } from "./fileDragDropHandlerUtils";
import { toast } from "sonner";
import { useState, useContext } from "react";
import { findParentWithClass } from "@/lib/utils";
import {
  DndContext,
  DragEndEvent,
  KeyboardSensor,
  PointerSensor,
  useSensors,
  useSensor,
} from "@dnd-kit/core";
import FileActionHandler from "../FileActionHandler";
import { fileActionAnimatorContext } from "@/components/FileSystem/FileActionAnimator";
import { useFilesStore } from "@/stores/filesStore";
import { useAppsStore } from "@/stores/appsStore";

export interface MainProps extends React.PropsWithChildren {}

function Main({ children }: MainProps) {
  const imageAssets = import.meta.glob<{
    default: string;
  }>("/src/assets/images/icons/*.{jpg,jpeg,png,svg}", { eager: true });

  const { fileActionAnimator, setFileActionAnimator } = useContext(
    fileActionAnimatorContext
  );

  const sensors = useSensors(
    useSensor(KeyboardSensor, {
      keyboardCodes: {
        start: [],
        cancel: [],
        end: [],
      },
    }),
    useSensor(PointerSensor)
  );

  const { updateFileProperties, moveFile } = useFilesStore();
  const { launchWindow } = useAppsStore();

  const [isDragStopped, setIsDragStopped] = useState(false);

  const onDragMove = (e: DragEndEvent) => {
    if (e.over) {
      const fileId = e.active.id.toString().split("-").slice(-1)[0];
      const activeElements = document.querySelectorAll(
        `[data-file-id="${fileId}"]`
      );

      if (activeElements.length) {
        // Set drag move status
        setIsDragStopped(true);

        // Set animator initial coordinate
        setFileActionAnimator({
          ...fileActionAnimator,
          from: {
            x: activeElements[0].getBoundingClientRect().x,
            y: activeElements[0].getBoundingClientRect().y,
          },
        });
      }
    }
  };

  const runAnimate = ({
    event,
    path,
    name,
  }: {
    event: DragEndEvent;
    path: string;
    name: string;
  }) => {
    // Update index & animated
    updateFileProperties({
      path: path,
      properties: {
        animated: true,
      },
      name,
    });

    // Set animation destination coordinate
    setTimeout(() => {
      const fileId = event.active.id.toString().split("-").slice(-1)[0];
      const activeElements = document.querySelectorAll(
        `[data-file-id="${fileId}"]`
      );
      const activeElement =
        activeElements.length > 1
          ? [...Array.from(activeElements)].find((activeElement) =>
              findParentWithClass(activeElement, "focus-state")
            )
          : activeElements[0];

      if (activeElement) {
        const markup = activeElement.cloneNode(true) as Element;
        markup.classList.remove("opacity-0");

        setFileActionAnimator({
          ...fileActionAnimator,
          element: markup.outerHTML,
          to: {
            x: activeElement.getBoundingClientRect().x,
            y: activeElement.getBoundingClientRect().y,
          },
        });

        setTimeout(() => {
          // Update animated
          updateFileProperties({
            path,
            properties: {
              animated: false,
            },
            name,
          });
        }, 200);
      }
    });
  };

  const onDragEnd = (event: DragEndEvent) => {
    if (isDragStopped) {
      // Reset drag move event
      setIsDragStopped(false);

      const { name, originPath, destinationPath, index, app } = getActionProps({
        event,
      });

      // Move file
      const moveFileResult = moveFile({
        originPath,
        destinationPath,
        name,
      });

      if (!moveFileResult.errorMessages.length) {
        updateFileProperties({
          path: destinationPath,
          properties: {
            index,
          },
          name,
        });

        toast("System Notification", {
          icon: (
            <img
              src={imageAssets["/src/assets/images/icons/guake.svg"].default}
            />
          ),
          description: `Starting to move file from "${originPath}" to "${destinationPath}".`,
          action: {
            label: "Dismiss",
            onClick: () => console.log("Starting to move file."),
          },
        });

        setTimeout(() => {
          toast("System Notification", {
            icon: (
              <img
                src={imageAssets["/src/assets/images/icons/guake.svg"].default}
              />
            ),
            description: `The file was successfully moved.`,
            action: {
              label: "Dismiss",
              onClick: () => console.log("Successfully moved."),
            },
          });
        }, 1000);
      } else {
        const findError = (code: string) =>
          moveFileResult.errorMessages.find((message) => message.code == code);

        if (findError("SAME_DIRECTORY")) {
          updateFileProperties({
            path: originPath,
            properties: {
              index,
            },
            name,
          });

          // Animate
          runAnimate({
            path: originPath,
            event,
            name,
          });
        }

        if (!findError("SAME_DIRECTORY") && findError("FILE_ALREADY_EXISTS")) {
          launchWindow({
            path: app?.path,
            component:
              ({ windowId }) =>
              () =>
                (
                  <FileActionHandler
                    {...{
                      actionType: "cut",
                      originPath,
                      destinationPath,
                      name,
                      appPath: app?.path,
                      windowId,
                    }}
                  />
                ),
          });
        }
      }
    }
  };

  return (
    <DndContext sensors={sensors} onDragMove={onDragMove} onDragEnd={onDragEnd}>
      {children}
    </DndContext>
  );
}

export default Main;
