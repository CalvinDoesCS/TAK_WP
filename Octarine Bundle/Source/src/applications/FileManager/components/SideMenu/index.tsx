import { menu } from "./menu";
import { useFilesStore } from "@/stores/filesStore";
import { type DirectoryListContextValue } from "../../context/directoryListContext";
import { cn } from "@/lib/utils";
import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/Base/Sidebar";

function Main({
  directoryList,
  setDirectoryList,
}: {
  directoryList: DirectoryListContextValue[];
  setDirectoryList: (directoryList: DirectoryListContextValue[]) => void;
}) {
  const filesStore = useFilesStore();

  return (
    <>
      <Sidebar className="absolute w-full h-auto [&>[data-sidebar]]:bg-transparent group-data-[side=left]:border-r-0">
        <SidebarContent className="px-2">
          {menu.map((menuItem, menuItemKey) => {
            return (
              <SidebarGroup key={menuItemKey} className="first:-mt-3 last:mb-3">
                <SidebarGroupLabel>{menuItem.title}</SidebarGroupLabel>
                <SidebarGroupContent>
                  <SidebarMenu>
                    {menuItem.menu.map((menuItem, menuItemKey) => (
                      <SidebarMenuItem key={menuItemKey}>
                        <SidebarMenuButton asChild>
                          <a
                            href=""
                            className={cn([
                              "px-4 py-2 h-auto hover:bg-foreground/[.03] [&.selected>svg]:stroke-[1.7] [&.selected]:font-medium [&.selected]:bg-foreground/[.06]",
                              {
                                selected:
                                  directoryList[0] &&
                                  directoryList[0].path == menuItem.pathname,
                              },
                            ])}
                            onClick={(e) => {
                              e.preventDefault();
                              setDirectoryList([
                                {
                                  path: menuItem.pathname,
                                  file: filesStore.selectFile(
                                    menuItem.pathname
                                  ),
                                  focus: false,
                                },
                              ]);
                            }}
                          >
                            {menuItem.icon}
                            <span>{menuItem.title}</span>
                          </a>
                        </SidebarMenuButton>
                      </SidebarMenuItem>
                    ))}
                  </SidebarMenu>
                </SidebarGroupContent>
              </SidebarGroup>
            );
          })}
        </SidebarContent>
      </Sidebar>
    </>
  );
}

export default Main;
