import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/Base/Select";
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
            <div className="font-medium">Notification Volume Hub</div>
            <div className="mt-1 mb-0.5 text-xs leading-normal text-muted-foreground text-justify">
              Quickly adjust notification volume settings and updates in one
              place. Located in the top-right corner, you can open or close the
              Hub by clicking the volume icon in the menu bar.
            </div>
          </div>
          <div className="flex flex-col px-3 border divide-y rounded-md bg-muted-foreground/[.01] shadow-sm">
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Notification Volume</div>
              <div className="flex items-center gap-3">
                <div className="">Mute</div>
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
              <div className="mr-auto font-medium">Notification Sound</div>
              <Select value="1">
                <SelectTrigger className="h-6 bg-transparent border-0 w-28 focus:ring-transparent focus:ring-offset-0">
                  <SelectValue placeholder="Select option" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="0">Chime</SelectItem>
                    <SelectItem value="1">Ding</SelectItem>
                    <SelectItem value="2">Echo</SelectItem>
                    <SelectItem value="3">Pop</SelectItem>
                    <SelectItem value="4">Alert</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Mute UI Sounds</div>
              <Switch id="airplane-mode" />
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Mute Mode</div>
              <Switch id="airplane-mode" />
            </div>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="font-medium">Equalizer</div>
          <div className="flex flex-col px-3 border divide-y rounded-md bg-muted-foreground/[.01] shadow-sm">
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Bass</div>
              <div className="flex items-center gap-3">
                <div className="">-5</div>
                <Slider
                  defaultValue={[50]}
                  max={100}
                  step={1}
                  className="w-28 @xl/window:w-56"
                />
                <div className="">+5</div>
              </div>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Treble</div>
              <div className="flex items-center gap-3">
                <div className="">-5</div>
                <Slider
                  defaultValue={[50]}
                  max={100}
                  step={1}
                  className="w-28 @xl/window:w-56"
                />
                <div className="">+5</div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
