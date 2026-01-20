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
import { Label } from "@/components/Base/Label";
import { RadioGroup, RadioGroupItem } from "@/components/Base/RadioGroup";
import Toolbar from "../../components/Toolbar";
import { ChevronRight } from "lucide-react";
import _ from "lodash";

function Main() {
  const applicationNotifications = [
    {
      appName: "Cineflix",
      permissions: "Badges, Sounds, Banners",
    },
    {
      appName: "ShopEase",
      permissions: "Alerts, Sounds, Lock Screen",
    },
    {
      appName: "TaskMaster",
      permissions: "Banners, Critical Alerts, Badges",
    },
    {
      appName: "HealthTrack",
      permissions: "Badges, Notification Center, Sounds",
    },
    {
      appName: "MusicStream",
      permissions: "Banners, Alerts, Lock Screen",
    },
    {
      appName: "QuickNews",
      permissions: "Sounds, Critical Alerts, Badges",
    },
    {
      appName: "TravelMate",
      permissions: "Lock Screen, Sounds, Alerts",
    },
    {
      appName: "LearnHub",
      permissions: "Banners, Notification Center, Sounds",
    },
    {
      appName: "FinancePro",
      permissions: "Critical Alerts, Badges, Sounds",
    },
    {
      appName: "FitLife",
      permissions: "Banners, Lock Screen, Notification Center",
    },
  ];

  const imageAssets = import.meta.glob<{
    default: string;
  }>("/src/assets/images/icons/**/*.{jpg,jpeg,png,svg}", { eager: true });

  return (
    <div className="flex flex-col w-full h-full">
      <Toolbar />
      <div className="flex flex-col px-4 py-6 overflow-y-auto scrollbar gap-7">
        <div className="flex flex-col gap-4">
          <div className="px-3 mb-2">
            <div className="font-medium">Notification Center</div>
            <div className="mt-1 mb-0.5 text-xs leading-normal text-muted-foreground text-justify">
              Notification Center shows your notifications in the top-right
              corner of your screen. You can show and hide Notification Center
              by clicking the clock in the menu bar.
            </div>
          </div>
          <div className="flex flex-col px-3 border divide-y rounded-md bg-muted-foreground/[.01] shadow-sm">
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Enable Notifications</div>
              <Switch id="airplane-mode" checked />
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Notification Sound</div>
              <Select value="1">
                <SelectTrigger className="w-40 h-6 bg-transparent border-0 focus:ring-transparent focus:ring-offset-0">
                  <SelectValue placeholder="Select option" />
                </SelectTrigger>
                <SelectContent>
                  <SelectGroup>
                    <SelectItem value="0">Always</SelectItem>
                    <SelectItem value="1">When Unlocked</SelectItem>
                    <SelectItem value="2">Never</SelectItem>
                  </SelectGroup>
                </SelectContent>
              </Select>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Duration</div>
              <div className="flex items-center gap-3">
                <div className="">1s</div>
                <Slider
                  defaultValue={[50]}
                  max={100}
                  step={1}
                  className="w-28 @xl/window:w-56"
                />
                <div className="">10s</div>
              </div>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Notification Position</div>
              <RadioGroup className="grid-cols-2" defaultValue="1">
                <div className="flex items-center space-x-2">
                  <RadioGroupItem value="1" id="r1" />
                  <Label className="font-normal" htmlFor="r1">
                    Top Left
                  </Label>
                </div>
                <div className="flex items-center space-x-2">
                  <RadioGroupItem value="2" id="r2" />
                  <Label className="font-normal" htmlFor="r2">
                    Top Right
                  </Label>
                </div>
              </RadioGroup>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Priority Notification</div>
              <Switch id="airplane-mode" />
            </div>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="font-medium">Application Notifications</div>
          <div className="flex flex-col px-3 border divide-y rounded-md bg-muted-foreground/[.02] shadow-sm">
            {applicationNotifications.map(
              (applicationNotification, applicationNotificationKey) => (
                <div
                  className="flex items-center py-3"
                  key={applicationNotificationKey}
                >
                  <div className="flex gap-3 mr-auto">
                    <div className="relative w-10 h-10 overflow-hidden rounded-md">
                      <img
                        className="absolute object-cover size-full"
                        src={_.shuffle(imageAssets)[0].default}
                      />
                    </div>
                    <div className="flex flex-col gap-0.5">
                      <div className="font-medium">
                        {applicationNotification.appName}
                      </div>
                      <div className="text-xs text-muted-foreground">
                        {applicationNotification.permissions}
                      </div>
                    </div>
                  </div>
                  <ChevronRight className="w-4 h-4" />
                </div>
              )
            )}
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
