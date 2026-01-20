import { type Actions as FilesStoreActions } from "@/stores/filesStore";
import { type Actions as AppsStoreActions } from "@/stores/appsStore";
import { type WindowContextInterface } from "@/components/Window/windowContext";
import { type ClipboardContextInterface } from "../ClipboardProvider/clipboardContext";
import { type RightClickMenuContext } from "@/components/RightClickMenu/rightClickMenuContext";
import { Copy, Scissors, Trash2, BookCopy } from "lucide-react";

const onRightClick = ({
  path,
  filesStoreActions,
  clipboardContext,
  rightClickMenuContext,
}: {
  path: string;
  filesStoreActions: FilesStoreActions;
  appsStoreActions: AppsStoreActions;
  clipboardContext: ClipboardContextInterface;
  windowContext: WindowContextInterface;
  rightClickMenuContext: RightClickMenuContext;
}) => {
  const { setRightClickMenu } = rightClickMenuContext;
  const { selectFile, deleteFile } = filesStoreActions;
  const { setClipboard } = clipboardContext;

  setRightClickMenu([
    {
      icon: <Copy className="w-4 h-4" />,
      title: "Copy",
      onClick: () =>
        setClipboard({
          actionType: "copy",
          path: path,
          selectedFiles: Object.entries(selectFile(path).entries)
            .filter(([fileName, file]) => file.selected)
            .map(([fileName, file]) => ({ fileName, file })),
        }),
    },
    {
      icon: <Scissors className="w-4 h-4" />,
      title: "Cut",
      onClick: () =>
        setClipboard({
          actionType: "cut",
          path: path,
          selectedFiles: Object.entries(selectFile(path).entries)
            .filter(([fileName, file]) => file.selected)
            .map(([fileName, file]) => ({ fileName, file })),
        }),
    },
    {
      title: "Separator",
    },
    {
      icon: <Trash2 className="w-4 h-4" />,
      title: "Move to Trash",
      onClick: () =>
        Object.entries(selectFile(path).entries)
          .filter(([fileName, file]) => file.selected)
          .map(([fileName]) =>
            deleteFile({
              path: path,
              name: fileName,
            })
          ),
    },
    {
      title: "Separator",
    },
    {
      icon: <BookCopy className="w-4 h-4" />,
      title: "Get Info",
    },
  ]);
};

export { onRightClick };
