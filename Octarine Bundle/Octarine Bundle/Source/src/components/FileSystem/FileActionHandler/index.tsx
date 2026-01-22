import FileMoveCopyConflictDialog from "../FileMoveCopyConflictDialog";
import { useAppsStore } from "@/stores/appsStore";
import { useFilesStore } from "@/stores/filesStore";

export interface MainProps extends React.ComponentPropsWithoutRef<"div"> {
  actionType: "copy" | "cut";
  name: string;
  originPath: string;
  destinationPath: string;
  appPath: string;
  windowId: string;
}

function Main({
  actionType,
  name,
  originPath,
  destinationPath,
  appPath,
  windowId,
}: MainProps) {
  const { closeWindow } = useAppsStore();
  const {
    copyAndReplaceFile,
    copyAndRenameFile,
    moveAndReplaceFile,
    moveAndRenameFile,
  } = useFilesStore();

  return (
    <FileMoveCopyConflictDialog
      actionType={actionType}
      fileName={name}
      confirm={(userAction) => {
        const params = {
          originPath,
          destinationPath,
          name,
        };

        if (actionType == "copy") {
          if (userAction == "replace") {
            copyAndReplaceFile(params);
          } else if (userAction == "keep-both") {
            copyAndRenameFile(params);
          }
        } else if (actionType == "cut") {
          if (userAction == "replace") {
            moveAndReplaceFile(params);
          } else if (userAction == "keep-both") {
            moveAndRenameFile(params);
          }
        }

        closeWindow({
          path: appPath,
          index: windowId,
        });
      }}
    />
  );
}

export default Main;
