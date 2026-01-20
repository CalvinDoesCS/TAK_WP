import { type Actions as FilesStoreActions } from "@/stores/filesStore";
import { type Actions as AppsStoreActions } from "@/stores/appsStore";
import { type WindowContextInterface } from "@/components/Window/windowContext";
import { type ClipboardContextInterface } from "../ClipboardProvider/clipboardContext";
import { type RightClickMenuContext } from "@/components/RightClickMenu/rightClickMenuContext";
import { onPaste } from "./onPaste";
import {
  ArrowUpDown,
  RefreshCcw,
  SquarePlus,
  Clipboard,
  Settings,
} from "lucide-react";

const onRightClick = ({
  path,
  filesStoreActions,
  appsStoreActions,
  windowContext,
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
  const { scoped } = windowContext;
  const { setRightClickMenu } = rightClickMenuContext;

  let rightClickMenu = [
    { icon: <ArrowUpDown className="w-4 h-4" />, title: "Sort by" },
    { icon: <RefreshCcw className="w-4 h-4" />, title: "Refresh" },
    { title: "Separator" },
    {
      icon: <Clipboard className="w-4 h-4" />,
      title: "Paste",
      onClick: () =>
        onPaste({
          path,
          clipboardContext,
          filesStoreActions,
          appsStoreActions,
          windowContext,
        }),
    },
    { title: "Separator" },
    { icon: <SquarePlus className="w-4 h-4" />, title: "New" },
  ];

  if (!scoped) {
    rightClickMenu = [
      ...rightClickMenu,
      ...[
        { title: "Separator" },
        {
          icon: <Settings className="w-4 h-4" />,
          title: "Desktop Properties",
        },
      ],
    ];
  }

  setRightClickMenu(rightClickMenu);
};

export { onRightClick };
