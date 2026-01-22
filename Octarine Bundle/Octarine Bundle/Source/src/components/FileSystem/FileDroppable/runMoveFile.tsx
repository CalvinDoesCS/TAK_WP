import { getNextIndex } from "../FileUtils/getNextIndex";
import { type Actions as FilesStoreActions } from "@/stores/filesStore";
import { type Actions as AppsStoreActions } from "@/stores/appsStore";
import { type WindowContextInterface } from "@/components/Window/windowContext";
import { type ClipboardContextInterface } from "../ClipboardProvider/clipboardContext";
import FileActionHandler from "../FileActionHandler";

const runMoveFile = ({
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
  const { selectFile, moveFile, updateFileProperties } = filesStoreActions;
  const { launchWindow } = appsStoreActions;
  const { clipboard } = clipboardContext;
  const { app } = windowContext;

  clipboard.selectedFiles.map((selectedFile) => {
    const nextIndex = getNextIndex(selectFile(path).entries);
    const moveFileResult = moveFile({
      originPath: clipboard.path,
      destinationPath: path,
      name: selectedFile.fileName,
    });

    if (!moveFileResult.errorMessages.length) {
      // Update index
      updateFileProperties({
        path,
        properties: {
          index: nextIndex,
        },
        name: selectedFile.fileName,
      });
    } else {
      const findError = (code: string) =>
        moveFileResult.errorMessages.find((message) => message.code == code);

      if (!findError("SAME_DIRECTORY") && findError("FILE_ALREADY_EXISTS")) {
        launchWindow({
          path: app.path,
          component:
            ({ windowId }) =>
            () =>
              (
                <FileActionHandler
                  {...{
                    actionType: "cut",
                    name: selectedFile.fileName,
                    originPath: clipboard.path,
                    destinationPath: path,
                    appPath: app.path,
                    windowId,
                  }}
                />
              ),
        });
      }
    }
  });
};

export { runMoveFile };
