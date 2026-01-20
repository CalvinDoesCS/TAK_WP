import { menu } from "./menu";
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

function Main() {
  return (
    <>
      <Sidebar className="absolute w-full h-full pb-24 [&>[data-sidebar]]:bg-transparent group-data-[side=left]:border-r-0">
        <SidebarContent className="px-2">
          {menu.map((menuItem, menuItemKey) => {
            return (
              <SidebarGroup key={menuItemKey} className="first:-mt-3 last:mb-3">
                <SidebarGroupLabel>{menuItem.title}</SidebarGroupLabel>
                <SidebarGroupContent>
                  <SidebarMenu>
                    {menuItem.menu.map((item, itemKey) => (
                      <SidebarMenuItem key={itemKey}>
                        <SidebarMenuButton asChild>
                          <a
                            href=""
                            className={cn([
                              "px-4 py-2 h-auto hover:bg-foreground/[.03] [&.selected>svg]:stroke-[1.7] [&.selected]:font-medium [&.selected]:bg-foreground/[.06]",
                              {
                                selected: !menuItemKey && !itemKey,
                              },
                            ])}
                          >
                            {item.icon}
                            <span>{item.title}</span>
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
