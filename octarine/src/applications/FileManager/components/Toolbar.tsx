import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/Base/Tooltip";
import {
  EllipsisVertical,
  ChevronLeft,
  ChevronRight,
  LayoutGrid,
  List,
  Columns,
  GalleryThumbnails,
} from "lucide-react";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/Base/DropdownMenu";
import { Separator } from "@/components/Base/Separator";
import { DraggableHandle } from "@/components/Window";

function Main() {
  return (
    <>
      <DraggableHandle className="flex items-center justify-between px-2 py-2.5 border-b bg-muted/50 z-50">
        <TooltipProvider delayDuration={0}>
          <div className="items-center w-full gap-1 hidden @lg/window:flex">
            <Tooltip>
              <TooltipTrigger asChild>
                <div className="px-3 py-2.5 rounded-md hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.05]">
                  <ChevronLeft className="w-4 h-4" />
                </div>
              </TooltipTrigger>
              <TooltipContent>
                <p>Back</p>
              </TooltipContent>
            </Tooltip>
            <Tooltip>
              <TooltipTrigger asChild>
                <div className="px-3 py-2.5 rounded-md hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.05]">
                  <ChevronRight className="w-4 h-4" />
                </div>
              </TooltipTrigger>
              <TooltipContent>
                <p>Next</p>
              </TooltipContent>
            </Tooltip>
            <div className="ml-3 text-base font-medium">Recents</div>
            <div className="mx-auto"></div>
            <Tooltip>
              <TooltipTrigger asChild>
                <div className="px-3 py-2.5 rounded-md hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.05]">
                  <LayoutGrid className="w-4 h-4" />
                </div>
              </TooltipTrigger>
              <TooltipContent>
                <p>Icons</p>
              </TooltipContent>
            </Tooltip>
            <Tooltip>
              <TooltipTrigger asChild>
                <div className="px-3 py-2.5 rounded-md hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.05]">
                  <List className="w-4 h-4" />
                </div>
              </TooltipTrigger>
              <TooltipContent>
                <p>List</p>
              </TooltipContent>
            </Tooltip>
            <Tooltip>
              <TooltipTrigger asChild>
                <div className="px-3 py-2.5 rounded-md hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.05] selected">
                  <Columns className="w-4 h-4" />
                </div>
              </TooltipTrigger>
              <TooltipContent>
                <p>Columns</p>
              </TooltipContent>
            </Tooltip>
            <Tooltip>
              <TooltipTrigger asChild>
                <div className="px-3 py-2.5 rounded-md hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.05]">
                  <GalleryThumbnails className="w-4 h-4" />
                </div>
              </TooltipTrigger>
              <TooltipContent>
                <p>Gallery</p>
              </TooltipContent>
            </Tooltip>
            <Separator orientation="vertical" className="h-5 mx-1 my-2" />
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <div>
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <div className="px-3 py-2.5 rounded-md hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.05]">
                        <EllipsisVertical className="w-4 h-4" />
                      </div>
                    </TooltipTrigger>
                    <TooltipContent>
                      <p>Actions</p>
                    </TooltipContent>
                  </Tooltip>
                </div>
              </DropdownMenuTrigger>
              <DropdownMenuContent className="w-40">
                <DropdownMenuItem>Open in New Tab</DropdownMenuItem>
                <DropdownMenuItem>Get Info</DropdownMenuItem>
                <DropdownMenuItem>Manage Store...</DropdownMenuItem>
                <DropdownMenuItem>Rename</DropdownMenuItem>
                <DropdownMenuItem>Copy</DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
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
              <DropdownMenuContent className="w-40">
                <DropdownMenuItem>Open in New Tab</DropdownMenuItem>
                <DropdownMenuItem>Get Info</DropdownMenuItem>
                <DropdownMenuItem>Manage Store...</DropdownMenuItem>
                <DropdownMenuItem>Rename</DropdownMenuItem>
                <DropdownMenuItem>Copy</DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </TooltipProvider>
      </DraggableHandle>
    </>
  );
}

export default Main;
