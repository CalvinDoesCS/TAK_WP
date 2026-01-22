import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/Base/Select";
import { useState } from "react";
import { Switch } from "@/components/Base/Switch";
import { Button } from "@/components/Base/Button";
import Toolbar from "../../components/Toolbar";
import { CalendarCog, Trash2 } from "lucide-react";
import { format } from "date-fns";
import { Calendar } from "@/components/Base/Calendar";
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from "@/components/Base/Popover";

function Main() {
  const [date, setDate] = useState<Date>();

  return (
    <div className="flex flex-col w-full h-full">
      <Toolbar />
      <div className="flex flex-col px-4 py-6 overflow-y-auto scrollbar gap-7">
        <div className="flex flex-col gap-4">
          <div className="px-3 mb-2">
            <div className="font-medium">File Management Hub</div>
            <div className="mt-1 mb-0.5 text-xs leading-normal text-muted-foreground text-justify">
              Quickly access file management alerts and updates at a glance.
              Located in the top-right corner, you can open or close the Hub by
              clicking the folder icon in the menu bar.
            </div>
          </div>
          <div className="flex flex-col px-3 border divide-y rounded-md bg-muted-foreground/[.01] shadow-sm">
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Cloud Access</div>
              <Switch id="airplane-mode" checked />
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Download Location</div>
              <Select value="1">
                <SelectTrigger className="w-32 h-6 bg-transparent border-0 focus:ring-transparent focus:ring-offset-0">
                  <SelectValue placeholder="Select option" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="0">Downloads</SelectItem>
                    <SelectItem value="1">Documents</SelectItem>
                    <SelectItem value="2">System</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Clear Temp Files</div>
              <Button size="sm" className="gap-2">
                <Trash2 className="w-4 h-4" /> Clear Temp
              </Button>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Auto Backup</div>
              <Switch id="airplane-mode" />
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Backup Schedule</div>
              <Popover>
                <PopoverTrigger asChild>
                  <div className="flex items-center gap-1 cursor-pointer">
                    <a className="px-1 py-1.5" href="">
                      <CalendarCog className="w-4 h-4" />
                    </a>
                    <div>
                      {date ? (
                        format(date, "PPP")
                      ) : (
                        <span>Set backup date</span>
                      )}
                    </div>
                  </div>
                </PopoverTrigger>
                <PopoverContent className="w-auto p-0">
                  <Calendar
                    mode="single"
                    selected={date}
                    onSelect={setDate}
                    initialFocus
                  />
                </PopoverContent>
              </Popover>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
