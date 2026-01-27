import { Label } from "@/components/Base/Label";
import { RadioGroup, RadioGroupItem } from "@/components/Base/RadioGroup";
import { Window, DraggableHandle, ControlButtons } from "@/components/Window";
import { Button } from "@/components/Base/Button";
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from "@/components/Base/Tooltip";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/Base/DropdownMenu";
import { EllipsisVertical } from "lucide-react";
import { ScrollArea } from "@/components/Base/ScrollArea";

function Main() {
  return (
    <>
      <Window
        x="center"
        y="10%"
        width={550}
        height={400}
        maxWidth={600}
        maxHeight={400}
      >
        <ControlButtons className="mt-5 pt-0.5 ml-5" />
        <DraggableHandle className="flex items-center px-2 py-2.5 border-b bg-muted/50 z-50">
          <TooltipProvider delayDuration={0}>
            <div className="px-3 py-1.5 text-base font-medium text-center hidden @lg/window:block w-full">
              System Report
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
        <ScrollArea className="h-full">
          <div className="flex flex-col h-full gap-8 p-5 mb-16">
            <RadioGroup defaultValue="comfortable">
              <div className="flex items-center space-x-5">
                <RadioGroupItem value="default" id="r1" />
                <Label htmlFor="r1">
                  <div className="text-base font-medium">
                    Interactive Report
                  </div>
                  <div className="text-muted-foreground mt-0.5 leading-normal">
                    Use this under most circumstances. It allows you to track
                    progress of the report, enter more details about the
                    problem, and take screenshots. It might omit some less-used
                    sections that take a long time to report.
                  </div>
                </Label>
              </div>
              <div className="flex items-center space-x-5">
                <RadioGroupItem value="comfortable" id="r2" />
                <Label htmlFor="r2">
                  <div className="text-base font-medium">Full Report</div>
                  <div className="text-muted-foreground mt-0.5 leading-normal">
                    Use this option for minimal system interference when your
                    device is unresponsive or too slow, or when you need all
                    report sections. Does not allow you to enter more details or
                    take additional screenshots.
                  </div>
                </Label>
              </div>
            </RadioGroup>
            <Button>Report</Button>
          </div>
        </ScrollArea>
      </Window>
    </>
  );
}

export default Main;
