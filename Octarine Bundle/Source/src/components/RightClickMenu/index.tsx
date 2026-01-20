import {
  ContextMenu,
  ContextMenuContent,
  ContextMenuItem,
  ContextMenuTrigger,
  ContextMenuSeparator,
  ContextMenuShortcut,
} from "@/components/Base/ContextMenu";
import { useState } from "react";
import {
  rightClickMenuContext,
  type RightClickMenuContextValue,
} from "./rightClickMenuContext";
import { useRightClickOptionStore } from "@/stores/rightClickOptionStore";

export interface MainProps extends React.PropsWithChildren {}

function Main({ children }: MainProps) {
  const { rightClickOption } = useRightClickOptionStore();
  const [menu, setMenu] = useState<RightClickMenuContextValue[]>([]);
  const [displayedMenu, setDisplayedMenu] = useState<
    RightClickMenuContextValue[]
  >([]);

  return (
    <rightClickMenuContext.Provider
      value={{
        rightClickMenu: menu,
        setRightClickMenu: (value) => setMenu(value),
      }}
    >
      <ContextMenu onOpenChange={(open) => open && setDisplayedMenu(menu)}>
        <ContextMenuContent className="w-64">
          {displayedMenu.map((menuItem, menuKey) =>
            menuItem.title != "Separator" ? (
              <ContextMenuItem
                key={menuKey}
                className="gap-3"
                onClick={() => menuItem.onClick && menuItem.onClick()}
              >
                {menuItem.icon}
                <div>{menuItem.title}</div>
                <ContextMenuShortcut>
                  {menuItem.shortcut || "⇧⌘S"}
                </ContextMenuShortcut>
              </ContextMenuItem>
            ) : (
              <ContextMenuSeparator key={menuKey} />
            )
          )}
        </ContextMenuContent>
        <ContextMenuTrigger
          disabled={!menu.length || !rightClickOption.isActive}
        >
          {children}
        </ContextMenuTrigger>
      </ContextMenu>
    </rightClickMenuContext.Provider>
  );
}

export default Main;
