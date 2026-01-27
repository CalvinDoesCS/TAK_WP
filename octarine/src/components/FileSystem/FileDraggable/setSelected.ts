import { File } from "../FileUtils/types";
import { type Actions as FilesStoreActions } from "@/stores/filesStore";
import { type ctrlKeyContextInterface } from "@/components/FileSystem/CtrlKeyProvider/ctrlKeyContext";

const setSelected = ({
  event,
  file,
  path,
  fileName,
  filesStoreActions,
  ctrlKeyContext,
}: {
  event: React.MouseEvent;
  file: File;
  path: string;
  fileName: string;
  filesStoreActions: FilesStoreActions;
  ctrlKeyContext: ctrlKeyContextInterface;
}) => {
  const { selectFile, updateFileProperties } = filesStoreActions;
  const { isCtrlKeyPressed } = ctrlKeyContext;

  const isRightClick = () => event.button === 2;
  const isFileNotSelected = () => !file.selected;

  if (!isRightClick() || (isRightClick() && isFileNotSelected())) {
    if (!isCtrlKeyPressed) {
      Object.entries(selectFile(path).entries).map(([fileKey]) => {
        updateFileProperties({
          path: path,
          properties: {
            selected: false,
          },
          name: fileKey,
        });
      });
    }

    updateFileProperties({
      path: path,
      properties: {
        selected: isCtrlKeyPressed ? !file.selected : true,
      },
      name: fileName,
    });
  }
};

export { setSelected };
