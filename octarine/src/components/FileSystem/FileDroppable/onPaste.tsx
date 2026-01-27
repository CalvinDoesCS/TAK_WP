import { type Actions as FilesStoreActions } from "@/stores/filesStore";
import { type Actions as AppsStoreActions } from "@/stores/appsStore";
import { type WindowContextInterface } from "@/components/Window/windowContext";
import { type ClipboardContextInterface } from "../ClipboardProvider/clipboardContext";
import { runCopyFile } from "./runCopyFile";
import { runMoveFile } from "./runMoveFile";
import { toast } from "sonner";

const imageAssets = import.meta.glob<{
  default: string;
}>("/src/assets/images/icons/*.{jpg,jpeg,png,svg}", { eager: true });

const onPaste = ({
  path,
  clipboardContext,
  windowContext,
  filesStoreActions,
  appsStoreActions,
}: {
  path: string;
  clipboardContext: ClipboardContextInterface;
  windowContext: WindowContextInterface;
  filesStoreActions: FilesStoreActions;
  appsStoreActions: AppsStoreActions;
}) => {
  const { clipboard } = clipboardContext;

  if (!clipboard.path.length) return;

  if (clipboard.actionType == "copy") {
    runCopyFile({
      path,
      clipboardContext,
      windowContext,
      filesStoreActions,
      appsStoreActions,
    });
  }

  if (clipboard.actionType == "cut") {
    runMoveFile({
      path,
      clipboardContext,
      windowContext,
      filesStoreActions,
      appsStoreActions,
    });
  }

  toast("System Notification", {
    icon: (
      <img src={imageAssets["/src/assets/images/icons/guake.svg"].default} />
    ),
    description: `Starting to move file from "${clipboard.path}" to "${path}".`,
    action: {
      label: "Dismiss",
      onClick: () => console.log("Starting to move file."),
    },
  });

  setTimeout(() => {
    toast("System Notification", {
      icon: (
        <img src={imageAssets["/src/assets/images/icons/guake.svg"].default} />
      ),
      description: `The file was successfully moved.`,
      action: {
        label: "Dismiss",
        onClick: () => console.log("Successfully moved."),
      },
    });
  }, 1000);
};

export { onPaste };
