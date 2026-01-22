import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/Base/Select";
import Toolbar from "../../components/Toolbar";

function Main() {
  return (
    <div className="flex flex-col w-full h-full">
      <Toolbar />
      <div className="flex flex-col px-4 py-6 overflow-y-auto scrollbar gap-7">
        <div className="flex flex-col gap-4">
          <div className="px-3 mb-2">
            <div className="font-medium">Language & Region Hub</div>
            <div className="mt-1 mb-0.5 text-xs leading-normal text-muted-foreground text-justify">
              Quickly access language and region settings and updates in one
              place. Located in the top-right corner, you can open or close the
              Hub by clicking the globe icon in the menu bar.
            </div>
          </div>
          <div className="flex flex-col px-3 border divide-y rounded-md bg-muted-foreground/[.01] shadow-sm">
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Language</div>
              <Select value="1">
                <SelectTrigger className="w-48 h-6 bg-transparent border-0 focus:ring-transparent focus:ring-offset-0">
                  <SelectValue placeholder="Select option" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="0">English (EN)</SelectItem>
                    <SelectItem value="1">Bahasa Indonesia (ID)</SelectItem>
                    <SelectItem value="2">Español (ES)</SelectItem>
                    <SelectItem value="3">Français (FR)</SelectItem>
                    <SelectItem value="4">Deutsch (DE)</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Date Format</div>
              <Select value="1">
                <SelectTrigger className="h-6 bg-transparent border-0 w-36 focus:ring-transparent focus:ring-offset-0">
                  <SelectValue placeholder="Select option" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="0">DD/MM/YYYY</SelectItem>
                    <SelectItem value="1">MM/DD/YYYY</SelectItem>
                    <SelectItem value="2">YYYY/MM/DD</SelectItem>
                    <SelectItem value="3">DD-MM-YYYY</SelectItem>
                    <SelectItem value="4">YYYY-MM-DD</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Time Format</div>
              <Select value="1">
                <SelectTrigger className="@md/content:w-64 h-6 bg-transparent border-0 focus:ring-transparent focus:ring-offset-0">
                  <SelectValue placeholder="Select option" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="0">24-Hour (e.g., 14:30)</SelectItem>
                    <SelectItem value="1">
                      12-Hour AM/PM (e.g., 2:30 PM)
                    </SelectItem>
                    <SelectItem value="2">HH AM/PM (e.g., 2:30 PM)</SelectItem>
                    <SelectItem value="3">HH:MM (e.g., 14:30:15)</SelectItem>
                    <SelectItem value="4">H AM/PM (e.g., 2:30 PM)</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Currency Format</div>
              <Select value="1">
                <SelectTrigger className="h-6 bg-transparent border-0 w-60 focus:ring-transparent focus:ring-offset-0">
                  <SelectValue placeholder="Select option" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="0">
                      USD ($) - United States Dollar
                    </SelectItem>
                    <SelectItem value="1">
                      IDR (Rp) - Indonesian Rupiah
                    </SelectItem>
                    <SelectItem value="2">EUR (€) - Euro</SelectItem>
                    <SelectItem value="3">JPY (¥) - Japanese Yen</SelectItem>
                    <SelectItem value="4">GBP (£) - British Pound</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Timezone</div>
              <Select value="1">
                <SelectTrigger className="h-6 bg-transparent border-0 @md/content:w-80 focus:ring-transparent focus:ring-offset-0">
                  <SelectValue placeholder="Select option" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="0">
                      GMT-8:00 - Pacific Time (US & Canada)
                    </SelectItem>
                    <SelectItem value="1">
                      GMT-5:00 - Eastern Time (US & Canada)
                    </SelectItem>
                    <SelectItem value="2">
                      GMT+1:00 - Central European Time
                    </SelectItem>
                    <SelectItem value="3">
                      GMT+7:00 - Western Indonesia Time
                    </SelectItem>
                    <SelectItem value="4">
                      GMT+9:00 - Japan Standard Time
                    </SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
