import { type Actions as FilesStoreActions } from "@/stores/filesStore";

const resetSelectedFiles = ({
  path,
  filesStoreActions,
}: {
  path: string;
  filesStoreActions: FilesStoreActions;
}) => {
  const { selectFile, updateFileProperties } = filesStoreActions;

  if (
    !Object.entries(selectFile(path).entries).find(([fileName, file]) =>
      Object.entries(file.entries).find(([fileName, file]) => file.selected)
    )
  ) {
    Object.entries(selectFile(path).entries).map(([fileName]) => {
      updateFileProperties({
        path,
        properties: {
          selected: false,
        },
        name: fileName,
      });
    });
  }
};

export { resetSelectedFiles };
