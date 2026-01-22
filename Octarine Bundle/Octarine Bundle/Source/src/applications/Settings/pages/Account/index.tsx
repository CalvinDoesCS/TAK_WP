import { Button } from "@/components/Base/Button";
import { Switch } from "@/components/Base/Switch";
import Toolbar from "../../components/Toolbar";
import {
  ChevronRight,
  Laptop,
  MonitorSmartphone,
  Pencil,
  Headset,
  Smartphone,
  SmartphoneCharging,
  TabletSmartphone,
  Keyboard,
} from "lucide-react";
import _ from "lodash";

function Main() {
  const devices = [
    {
      username: "Left4code",
      deviceName: 'Macbook Pro 16"',
      icon: Laptop,
    },
    {
      username: "TechGuru",
      deviceName: "Dell XPS 13",
      icon: MonitorSmartphone,
    },
    {
      username: "CodeMaster",
      deviceName: "HP Spectre x360",
      icon: Smartphone,
    },
    {
      username: "GamerTag",
      deviceName: "Alienware M15",
      icon: SmartphoneCharging,
    },
    {
      username: "DesignPro",
      deviceName: 'iMac 27"',
      icon: Headset,
    },
    {
      username: "PixelCrafter",
      deviceName: "Microsoft Surface Book 3",
      icon: TabletSmartphone,
    },
    {
      username: "DataGeek",
      deviceName: "Lenovo ThinkPad X1 Carbon",
      icon: Keyboard,
    },
  ];

  const imageAssets = import.meta.glob<{
    default: string;
  }>("/src/assets/images/profiles/**/*.{jpg,jpeg,png,svg}", { eager: true });

  return (
    <div className="flex flex-col w-full h-full">
      <Toolbar />
      <div className="flex flex-col px-4 py-6 overflow-y-auto scrollbar gap-7">
        <div className="flex flex-col gap-4">
          <div className="flex flex-col items-center mt-3 mb-6">
            <div className="relative w-24 h-24 rounded-full shadow-sm">
              <img
                className="absolute object-cover border-2 rounded-full size-full"
                src={
                  imageAssets[
                    "/src/assets/images/profiles/" +
                      "profile-" +
                      _.random(1, 2) +
                      ".jpg"
                  ].default
                }
              />
              <a
                className="absolute bottom-0 right-0 flex items-center justify-center mb-1 border rounded-full shadow-sm w-7 h-7 bg-background"
                href=""
              >
                <Pencil className="w-3 h-3" />
              </a>
            </div>
            <div className="mt-2 text-lg font-medium">Ethan Hunt</div>
            <div className="text-muted-foreground">ethanhunt@left4code.com</div>
          </div>
          <div className="flex flex-col px-3 border divide-y rounded-md bg-muted-foreground/[.01] shadow-sm">
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Username</div>
              <div className="flex items-center gap-1">
                <div>Ethanhunt007</div>
                <a className="px-1 py-1.5" href="">
                  <Pencil className="w-3 h-3" />
                </a>
              </div>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Password</div>
              <a className="px-1 py-1.5" href="">
                <ChevronRight className="w-4 h-4" />
              </a>
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">OAuth Apps</div>
              <Switch id="oauth-apps" checked />
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">2FA</div>
              <Switch id="2fa" />
            </div>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="font-medium">Devices</div>
          <div className="flex flex-col px-3 border divide-y rounded-md bg-muted-foreground/[.02] shadow-sm">
            {devices.map((device, deviceKey) => (
              <div className="flex items-center py-3" key={deviceKey}>
                <div className="flex gap-3.5 mr-auto">
                  <div className="flex items-center justify-center w-10 h-10 rounded-md bg-muted-foreground/15">
                    <device.icon className="w-5 h-5 [&.lucide]:stroke-1 fill-muted-foreground/20" />
                  </div>
                  <div className="flex flex-col gap-0.5">
                    <div className="font-medium">{device.username}</div>
                    <div className="text-xs text-muted-foreground">
                      {device.deviceName}
                    </div>
                  </div>
                </div>
                <ChevronRight className="w-4 h-4" />
              </div>
            ))}
          </div>
          <div className="flex -mt-1.5 mb-5 gap-2 flex-col @md/content:flex-row">
            <Button className="w-full @md/content:w-auto" size="sm">
              Sign Out
            </Button>
            <Button size="sm" className="ml-auto w-full @md/content:w-auto">
              About Octarine ID & Privacy
            </Button>
            <Button className="w-full @md/content:w-auto" size="sm">
              ?
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
