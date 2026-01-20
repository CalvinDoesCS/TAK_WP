import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/Base/Tooltip";
import {
  EllipsisVertical,
  SquarePen,
  List,
  Columns,
  GalleryThumbnails,
  Search,
} from "lucide-react";
import { Input } from "@/components/Base/Input";
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
          <div className="items-center w-full gap-1 hidden @lg/window:flex ml-4">
            <Tooltip>
              <TooltipTrigger asChild>
                <div className="px-3 py-2.5 rounded-md hover:bg-foreground/[.03] [&.selected]:bg-foreground/[.05]">
                  <SquarePen className="w-4 h-4" />
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
            <div className="mx-auto">
              <div className="text-xs text-center text-muted-foreground/80 @6xl/window:block hidden">
                Created: 29 October 2049 at 13.52
              </div>
            </div>
            <div className="relative">
              <Search className="absolute inset-y-0 left-0 w-4 h-4 my-auto ml-3.5 text-muted-foreground/90" />
              <Input
                type="text"
                placeholder="Search"
                className="h-auto pl-10 w-72 border-muted-foreground/15 bg-background focus-visible:ring-transparent focus-visible:ring-offset-0 placeholder:text-muted-foreground/70"
              />
            </div>
          </div>
          <div className="flex items-center w-full gap-1 @lg/window:hidden justify-end">
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
