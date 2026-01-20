import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/Base/Tooltip";
import { EllipsisVertical, ChevronLeft, ChevronRight } from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/Base/DropdownMenu";
import { Link, useLocation } from "react-router-dom";
import { DraggableHandle } from "@/components/Window";
import { menu } from "./SideMenu/menu";

function Main() {
  const location = useLocation();

  return (
    <>
      <DraggableHandle className="flex items-center justify-between px-2 py-2.5 border-b bg-muted/50 z-50">
        <TooltipProvider delayDuration={0}>
          <div className="items-center w-full gap-1 hidden @lg/window:flex">
            <Tooltip>
              <TooltipTrigger asChild>
                <div className="px-3 py-2.5 rounded-md hover:bg-foreground/[.03]">
                  <ChevronLeft className="w-4 h-4" />
                </div>
              </TooltipTrigger>
              <TooltipContent>
                <p>Back</p>
              </TooltipContent>
            </Tooltip>
            <Tooltip>
              <TooltipTrigger asChild>
                <div className="px-3 py-2.5 rounded-md hover:bg-foreground/[.03]">
                  <ChevronRight className="w-4 h-4" />
                </div>
              </TooltipTrigger>
              <TooltipContent>
                <p>Next</p>
              </TooltipContent>
            </Tooltip>
            <div className="ml-3 text-base font-medium">
              {menu
                .map((menuItem) => {
                  return menuItem.menu.find(
                    (menuItem) => menuItem.pathname == location.pathname
                  )?.title;
                })
                .find((menuItem) => menuItem)}
            </div>
          </div>
          <div className="flex items-center w-full gap-1 ml-24 @lg/window:hidden">
            <div className="text-base font-medium">Recents</div>
            <div className="mx-auto"></div>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <div>
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <div className="px-3 py-2.5 rounded-md hover:bg-foreground/[.03]">
                        <EllipsisVertical className="w-4 h-4" />
                      </div>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Actions</p>
                    </TooltipContent>
                  </Tooltip>
                </div>
              </DropdownMenuTrigger>
              <DropdownMenuContent>
                {menu.map((menuItem) =>
                  menuItem.menu.map((menu, menuKey) => (
                    <DropdownMenuItem asChild key={menuKey}>
                      <Link to={menu.pathname}>{menu.title}</Link>
                    </DropdownMenuItem>
                  ))
                )}
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </TooltipProvider>
      </DraggableHandle>
    </>
  );
}

export default Main;
