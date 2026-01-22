import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/Base/Tabs";
import {
  Forward,
  Reply,
  ReplyAll,
  Clock,
  Trash2,
  ArchiveX,
  Archive,
  EllipsisVertical,
} from "lucide-react";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/Base/Tooltip";
import {
  ResizableHandle,
  ResizablePanel,
  ResizablePanelGroup,
} from "@/components/Base/Resizable";
import { Input } from "@/components/Base/Input";
import { Separator } from "@/components/Base/Separator";
import { ScrollArea } from "@/components/Base/ScrollArea";
import { Textarea } from "@/components/Base/Textarea";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/Base/DropdownMenu";
import { Label } from "@/components/Base/Label";
import { Switch } from "@/components/Base/Switch";
import { Button } from "@/components/Base/Button";
import { Avatar, AvatarFallback } from "@/components/Base/Avatar";
import { DraggableHandle } from "@/components/Window";
import { Search } from "lucide-react";

function Main() {
  return (
    <ResizablePanelGroup direction="horizontal">
      <ResizablePanel defaultSize={35} minSize={40} maxSize={50}>
        <Tabs defaultValue="all-mail" asChild>
          <div className="flex flex-col h-full">
            <DraggableHandle className="flex items-center justify-end @lg/window:justify-between px-4 py-2.5 border-b">
              <div className="text-base font-medium hidden @lg/window:block">
                Inbox
              </div>
              <TabsList className="grid grid-cols-2">
                <TabsTrigger value="all-mail">All Mail</TabsTrigger>
                <TabsTrigger value="unread">Unread</TabsTrigger>
              </TabsList>
            </DraggableHandle>
            <div className="relative p-4">
              <Search className="absolute inset-y-0 w-4 h-4 my-auto ml-3" />
              <Input type="email" placeholder="Search mail" className="pl-9" />
            </div>
            <TabsContent asChild value="all-mail">
              <ScrollArea className="h-full px-4">
                <div className="flex flex-col gap-3 mb-4">
                  <div className="flex flex-col gap-1 p-4 border rounded-md [&.selected]:bg-muted selected">
                    <div className="flex items-center justify-between">
                      <div className="font-medium">William Smith</div>
                      <div className="text-xs">6 months ago</div>
                    </div>
                    <div className="text-xs">Meeting Tomorrow</div>
                    <div className="mt-1 mb-1.5 text-xs line-clamp-2 text-muted-foreground">
                      Hi, let's have a meeting tomorrow to discuss the project.
                      I've been reviewing the project details and have some
                      ideas I'd like to share. It's crucial that we align on our
                      next steps to ensure the project's success. Please come
                      prepared with any questions or insights you may have.
                      Looking forward to
                    </div>
                    <div className="flex gap-2">
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        meeting
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground selected">
                        work
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        important
                      </div>
                    </div>
                  </div>
                  <div className="flex flex-col gap-1 p-4 border rounded-md [&.selected]:bg-muted">
                    <div className="flex items-center justify-between">
                      <div className="font-medium">William Smith</div>
                      <div className="text-xs">6 months ago</div>
                    </div>
                    <div className="text-xs">Meeting Tomorrow</div>
                    <div className="mt-1 mb-1.5 text-xs line-clamp-2 text-muted-foreground">
                      Hi, let's have a meeting tomorrow to discuss the project.
                      I've been reviewing the project details and have some
                      ideas I'd like to share. It's crucial that we align on our
                      next steps to ensure the project's success. Please come
                      prepared with any questions or insights you may have.
                      Looking forward to
                    </div>
                    <div className="flex gap-2">
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        meeting
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground selected">
                        work
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        important
                      </div>
                    </div>
                  </div>
                  <div className="flex flex-col gap-1 p-4 border rounded-md [&.selected]:bg-muted">
                    <div className="flex items-center justify-between">
                      <div className="font-medium">William Smith</div>
                      <div className="text-xs">6 months ago</div>
                    </div>
                    <div className="text-xs">Meeting Tomorrow</div>
                    <div className="mt-1 mb-1.5 text-xs line-clamp-2 text-muted-foreground">
                      Hi, let's have a meeting tomorrow to discuss the project.
                      I've been reviewing the project details and have some
                      ideas I'd like to share. It's crucial that we align on our
                      next steps to ensure the project's success. Please come
                      prepared with any questions or insights you may have.
                      Looking forward to
                    </div>
                    <div className="flex gap-2">
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        meeting
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground selected">
                        work
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        important
                      </div>
                    </div>
                  </div>
                  <div className="flex flex-col gap-1 p-4 border rounded-md [&.selected]:bg-muted">
                    <div className="flex items-center justify-between">
                      <div className="font-medium">William Smith</div>
                      <div className="text-xs">6 months ago</div>
                    </div>
                    <div className="text-xs">Meeting Tomorrow</div>
                    <div className="mt-1 mb-1.5 text-xs line-clamp-2 text-muted-foreground">
                      Hi, let's have a meeting tomorrow to discuss the project.
                      I've been reviewing the project details and have some
                      ideas I'd like to share. It's crucial that we align on our
                      next steps to ensure the project's success. Please come
                      prepared with any questions or insights you may have.
                      Looking forward to
                    </div>
                    <div className="flex gap-2">
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        meeting
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground selected">
                        work
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        important
                      </div>
                    </div>
                  </div>
                  <div className="flex flex-col gap-1 p-4 border rounded-md [&.selected]:bg-muted">
                    <div className="flex items-center justify-between">
                      <div className="font-medium">William Smith</div>
                      <div className="text-xs">6 months ago</div>
                    </div>
                    <div className="text-xs">Meeting Tomorrow</div>
                    <div className="mt-1 mb-1.5 text-xs line-clamp-2 text-muted-foreground">
                      Hi, let's have a meeting tomorrow to discuss the project.
                      I've been reviewing the project details and have some
                      ideas I'd like to share. It's crucial that we align on our
                      next steps to ensure the project's success. Please come
                      prepared with any questions or insights you may have.
                      Looking forward to
                    </div>
                    <div className="flex gap-2">
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        meeting
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground selected">
                        work
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        important
                      </div>
                    </div>
                  </div>
                  <div className="flex flex-col gap-1 p-4 border rounded-md [&.selected]:bg-muted">
                    <div className="flex items-center justify-between">
                      <div className="font-medium">William Smith</div>
                      <div className="text-xs">6 months ago</div>
                    </div>
                    <div className="text-xs">Meeting Tomorrow</div>
                    <div className="mt-1 mb-1.5 text-xs line-clamp-2 text-muted-foreground">
                      Hi, let's have a meeting tomorrow to discuss the project.
                      I've been reviewing the project details and have some
                      ideas I'd like to share. It's crucial that we align on our
                      next steps to ensure the project's success. Please come
                      prepared with any questions or insights you may have.
                      Looking forward to
                    </div>
                    <div className="flex gap-2">
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        meeting
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground selected">
                        work
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        important
                      </div>
                    </div>
                  </div>
                  <div className="flex flex-col gap-1 p-4 border rounded-md [&.selected]:bg-muted">
                    <div className="flex items-center justify-between">
                      <div className="font-medium">William Smith</div>
                      <div className="text-xs">6 months ago</div>
                    </div>
                    <div className="text-xs">Meeting Tomorrow</div>
                    <div className="mt-1 mb-1.5 text-xs line-clamp-2 text-muted-foreground">
                      Hi, let's have a meeting tomorrow to discuss the project.
                      I've been reviewing the project details and have some
                      ideas I'd like to share. It's crucial that we align on our
                      next steps to ensure the project's success. Please come
                      prepared with any questions or insights you may have.
                      Looking forward to
                    </div>
                    <div className="flex gap-2">
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        meeting
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground selected">
                        work
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        important
                      </div>
                    </div>
                  </div>
                  <div className="flex flex-col gap-1 p-4 border rounded-md [&.selected]:bg-muted">
                    <div className="flex items-center justify-between">
                      <div className="font-medium">William Smith</div>
                      <div className="text-xs">6 months ago</div>
                    </div>
                    <div className="text-xs">Meeting Tomorrow</div>
                    <div className="mt-1 mb-1.5 text-xs line-clamp-2 text-muted-foreground">
                      Hi, let's have a meeting tomorrow to discuss the project.
                      I've been reviewing the project details and have some
                      ideas I'd like to share. It's crucial that we align on our
                      next steps to ensure the project's success. Please come
                      prepared with any questions or insights you may have.
                      Looking forward to
                    </div>
                    <div className="flex gap-2">
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        meeting
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground selected">
                        work
                      </div>
                      <div className="text-xs font-medium py-1 rounded-md px-2.5 [&.selected]:bg-primary [&.selected]:text-primary-foreground">
                        important
                      </div>
                    </div>
                  </div>
                </div>
              </ScrollArea>
            </TabsContent>
          </div>
        </Tabs>
      </ResizablePanel>
      <ResizableHandle withHandle className="hidden @2xl/content:flex" />
      <ResizablePanel className="hidden @2xl/content:block">
        <div className="flex flex-col h-full">
          <DraggableHandle className="flex items-center justify-between px-2 py-2.5 border-b">
            <TooltipProvider delayDuration={0}>
              <div className="flex w-full gap-1">
                <Tooltip>
                  <TooltipTrigger asChild>
                    <div className="p-3 rounded-md hover:bg-muted">
                      <Archive className="w-4 h-4" />
                    </div>
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>Archive</p>
                  </TooltipContent>
                </Tooltip>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <div className="p-3 rounded-md hover:bg-muted">
                      <ArchiveX className="w-4 h-4" />
                    </div>
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>Move to junk</p>
                  </TooltipContent>
                </Tooltip>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <div className="p-3 rounded-md hover:bg-muted">
                      <Trash2 className="w-4 h-4" />
                    </div>
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>Move to trash</p>
                  </TooltipContent>
                </Tooltip>
                <Separator
                  orientation="vertical"
                  className="h-auto mx-1 my-2"
                />
                <Tooltip>
                  <TooltipTrigger asChild>
                    <div className="p-3 rounded-md hover:bg-muted">
                      <Clock className="w-4 h-4" />
                    </div>
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>Snooze</p>
                  </TooltipContent>
                </Tooltip>
                <div className="mx-auto"></div>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <div className="p-3 rounded-md hover:bg-muted">
                      <Reply className="w-4 h-4" />
                    </div>
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>Reply</p>
                  </TooltipContent>
                </Tooltip>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <div className="p-3 rounded-md hover:bg-muted">
                      <ReplyAll className="w-4 h-4" />
                    </div>
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>Reply all</p>
                  </TooltipContent>
                </Tooltip>
                <Tooltip>
                  <TooltipTrigger asChild>
                    <div className="p-3 rounded-md hover:bg-muted">
                      <Forward className="w-4 h-4" />
                    </div>
                  </TooltipTrigger>
                  <TooltipContent>
                    <p>Forward</p>
                  </TooltipContent>
                </Tooltip>
                <Separator
                  orientation="vertical"
                  className="h-auto mx-1 my-2"
                />
                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <div>
                      <Tooltip>
                        <TooltipTrigger asChild>
                          <div className="p-3 rounded-md hover:bg-muted">
                            <EllipsisVertical className="w-4 h-4" />
                          </div>
                        </TooltipTrigger>
                        <TooltipContent>
                          <p>Mark as</p>
                        </TooltipContent>
                      </Tooltip>
                    </div>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent>
                    <DropdownMenuItem>Mark as unread</DropdownMenuItem>
                    <DropdownMenuItem>Star thread</DropdownMenuItem>
                    <DropdownMenuItem>Add label</DropdownMenuItem>
                    <DropdownMenuItem>Mute thread</DropdownMenuItem>
                  </DropdownMenuContent>
                </DropdownMenu>
              </div>
            </TooltipProvider>
          </DraggableHandle>
          <div className="flex w-full gap-4 p-4 border-b">
            <Avatar>
              <AvatarFallback>CN</AvatarFallback>
            </Avatar>
            <div className="flex flex-col w-full gap-1">
              <div className="flex items-center justify-between">
                <div className="font-medium">William Smith</div>
                <div className="text-xs text-muted-foreground">
                  Oct 22, 2023, 9:00:00 AM
                </div>
              </div>
              <div className="text-xs">Meeting Tomorrow</div>
              <div className="text-xs">Reply-To: williamsmith@example.com</div>
            </div>
          </div>
          <ScrollArea className="h-full p-4">
            Hi,
            <br />
            <br />
            Let's have a meeting tomorrow to discuss the project. I've been
            reviewing the project details and have some ideas I'd like to share.
            It's crucial that we align on our next steps to ensure the project's
            success.
            <br />
            <br />
            Please come prepared with any questions or insights you may have.
            Looking forward to our meeting!
            <br />
            <br />
            Best regards, William
          </ScrollArea>
          <div className="flex flex-col gap-4 p-4 border-t">
            <Textarea className="p-4" placeholder="Reply William Smith..." />
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-2">
                <Switch
                  className="h-5 w-9 [&>span]:w-4 [&>span]:h-4 [&>span]:data-[state=checked]:translate-x-4"
                  id="airplane-mode"
                />
                <Label className="text-xs" htmlFor="airplane-mode">
                  Mute this thread
                </Label>
              </div>
              <Button className="h-8 px-3 text-xs">Send</Button>
            </div>
          </div>
        </div>
      </ResizablePanel>
    </ResizablePanelGroup>
  );
}

export default Main;
