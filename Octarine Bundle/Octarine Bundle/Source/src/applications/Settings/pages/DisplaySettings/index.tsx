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
            <div className="font-medium">Display Settings Hub</div>
            <div className="mt-1 mb-0.5 text-xs leading-normal text-muted-foreground text-justify">
              Easily access display settings and updates at a glance. Located in
              the top-right corner, you can open or close the Hub by clicking
              the display icon in the menu bar.
            </div>
          </div>
          <div className="flex flex-col px-3 border divide-y rounded-md bg-muted-foreground/[.01] shadow-sm">
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Screen Resolution</div>
              <Select value="1">
                <SelectTrigger className="w-32 h-6 bg-transparent border-0 focus:ring-transparent focus:ring-offset-0">
                  <SelectValue placeholder="Select option" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="0">1920x1080</SelectItem>
                    <SelectItem value="1">1280x720</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Zoom</div>
              <div className="flex items-center gap-3">
                <div className="">50%</div>
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
              <div className="mr-auto font-medium">Refresh Rate</div>
              <Select value="1">
                <SelectTrigger className="w-24 h-6 bg-transparent border-0 focus:ring-transparent focus:ring-offset-0">
                  <SelectValue placeholder="Select option" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="0">60Hz</SelectItem>
                    <SelectItem value="1">120Hz</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Fullscreen</div>
              <Switch id="airplane-mode" />
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Enable Animations</div>
              <Switch id="airplane-mode" checked />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
