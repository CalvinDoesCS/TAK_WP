import { getNextIndex } from "../FileUtils/getNextIndex";
import { type Actions as FilesStoreActions } from "@/stores/filesStore";
import { type Actions as AppsStoreActions } from "@/stores/appsStore";
import { type WindowContextInterface } from "@/components/Window/windowContext";
import { type ClipboardContextInterface } from "../ClipboardProvider/clipboardContext";
import FileActionHandler from "../FileActionHandler";

const runCopyFile = ({
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
  const { selectFile, copyFile, copyAndRenameFile, updateFileProperties } =
    filesStoreActions;
  const { launchWindow } = appsStoreActions;
  const { clipboard } = clipboardContext;
  const { app } = windowContext;

  clipboard.selectedFiles.map((selectedFile) => {
    const nextIndex = getNextIndex(selectFile(path).entries);
    const copyFileResult = copyFile({
      originPath: clipboard.path,
      destinationPath: path,
      name: selectedFile.fileName,
    });

    if (!copyFileResult.errorMessages.length) {
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
        copyFileResult.errorMessages.find((message) => message.code == code);

      if (findError("SAME_DIRECTORY")) {
        copyAndRenameFile({
          originPath: clipboard.path,
          destinationPath: path,
          name: selectedFile.fileName,
        });
      }

      if (!findError("SAME_DIRECTORY") && findError("FILE_ALREADY_EXISTS")) {
        launchWindow({
          path: app.path,
          component:
            ({ windowId }) =>
            () =>
              (
                <FileActionHandler
                  {...{
                    actionType: "copy",
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

export { runCopyFile };
