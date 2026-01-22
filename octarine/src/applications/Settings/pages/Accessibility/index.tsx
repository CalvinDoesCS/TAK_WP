import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/Base/Select";
import * as React from "react";
import { Switch } from "@/components/Base/Switch";
import { Slider } from "@/components/Base/Slider";
import Toolbar from "../../components/Toolbar";

function Main() {
  return (
    <div className="flex flex-col w-full h-full">
      <Toolbar />
      <div className="flex flex-col px-4 py-6 overflow-y-auto scrollbar gap-7">
        <div className="flex flex-col gap-4">
          <div className="px-3 mb-2">
            <div className="font-medium">Accessibility Hub</div>
            <div className="mt-1 mb-0.5 text-xs leading-normal text-muted-foreground text-justify">
              Effortlessly access accessibility settings and updates in one
              place. Located in the top-right corner, you can open or close the
              Hub by clicking the accessibility icon in the menu bar.
            </div>
          </div>
          <div className="flex flex-col px-3 border divide-y rounded-md bg-muted-foreground/[.01] shadow-sm">
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">High Contrast Mode</div>
              <Switch id="airplane-mode" />
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Text Size</div>
              <div className="flex items-center gap-3">
                <div className="">100%</div>
                <Slider
                  defaultValue={[50]}
                  max={100}
                  step={1}
                  className="w-28 @xl/window:w-56"
                />
                <div className="">200%</div>
              </div>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Screen Reader</div>
              <Select value="1">
                <SelectTrigger className="w-64 h-6 bg-transparent border-0 focus:ring-transparent focus:ring-offset-0">
                  <SelectValue placeholder="Select option" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="0">None - No screen reader</SelectItem>
                    <SelectItem value="1">
                      VoiceOver - Built-in screen reader
                    </SelectItem>
                    <SelectItem value="2">
                      NVDA - NonVisual Desktop Access
                    </SelectItem>
                    <SelectItem value="3">
                      JAWS - Job Access With Speech
                    </SelectItem>
                    <SelectItem value="4">
                      Narrator - Windows screen reader
                    </SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Keyboard Navigation</div>
              <Switch id="airplane-mode" checked />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
