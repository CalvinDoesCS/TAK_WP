import {
  overlays,
  useDesktopPropertiesStore,
} from "@/stores/desktopPropertiesStore";
import { Skeleton } from "@/components/Base/Skeleton";
import Toolbar from "../../components/Toolbar";
import { PanelsTopLeft } from "lucide-react";
import { cn } from "@/lib/utils";
import { Switch } from "@/components/Base/Switch";
import { Slider } from "@/components/Base/Slider";
import { useEffect, useState } from "react";
import _ from "lodash";

function Main() {
  const [isLoading, setIsLoading] = useState(true);
  const { setDekstopProperties, desktopProperties } =
    useDesktopPropertiesStore();

  useEffect(() => {
    setTimeout(() => {
      setIsLoading(false);
    }, 1000);
  }, []);

  return (
    <div className="flex flex-col w-full h-full">
      <Toolbar />
      <div className="flex flex-col px-4 py-6 overflow-y-auto scrollbar gap-7">
        <div className="flex flex-col gap-4">
          <div className="px-3 mb-2">
            <div className="font-medium">Overlay Settings</div>
            <div className="mt-1 mb-0.5 text-xs leading-normal text-muted-foreground text-justify">
              Add a stylish gradient overlay to your desktop wallpaper. Adjust
              the overlay color, direction, and opacity to create a unique look
              and personalize your workspace further.
            </div>
          </div>
          <div className="flex flex-col px-3 border divide-y rounded-md bg-muted-foreground/[.01] shadow-sm">
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Show Overlay</div>
              <Switch
                id="airplane-mode"
                checked={desktopProperties.overlay.isShow}
                onClick={() =>
                  setDekstopProperties({
                    overlay: { isShow: !desktopProperties.overlay.isShow },
                  })
                }
              />
            </div>
            <div className="flex @md/content:items-center gap-3 @md/content:gap-5 py-4 flex-col @md/content:flex-row">
              <div className="mr-auto font-medium">Opacity</div>
              <Slider
                defaultValue={[desktopProperties.overlay.opacity]}
                max={1}
                step={0.1}
                className="w-28 @xl/window:w-56"
                onValueChange={(val) => {
                  setDekstopProperties({
                    overlay: { opacity: val[0] },
                  });
                }}
              />
            </div>
          </div>
        </div>
        {overlays.map((overlay, overlayKey) => (
          <div className="flex flex-col gap-4" key={overlayKey}>
            <div className="flex items-center">
              <div className="font-medium">{overlay.title}</div>
              <a href="" className="ml-auto text-xs text-muted-foreground">
                Show All ({_.random(10, 40)})
              </a>
            </div>
            <div className="w-full h-full">
              <div className="flex gap-3.5 overflow-x-auto rounded-md snap-x snap-mandatory [&::-webkit-scrollbar]:hidden [scrollbar-width:none]">
                {overlay.overlays.map(({ title, overlay }, overlayKey) => (
                  <div
                    className="cursor-pointer snap-start"
                    key={overlayKey}
                    onClick={() =>
                      setDekstopProperties({
                        overlay: {
                          gradient: overlay,
                        },
                      })
                    }
                  >
                    <div className="relative h-20 overflow-hidden rounded-md w-[8.69rem]">
                      <Skeleton
                        className={cn([
                          "transition-all delay-200 absolute inset-0 bg-muted-foreground/20",
                          { "opacity-100": isLoading },
                          { "opacity-0": !isLoading },
                        ])}
                      />
                      <div
                        className={cn([
                          "transition-all duration-700 absolute bg-gradient-to-bl size-full",
                          overlay,
                          { "opacity-100": !isLoading },
                          { "opacity-0": isLoading },
                        ])}
                      ></div>
                      <div
                        className={cn([
                          "absolute z-10 w-full h-full",
                          {
                            "bg-gradient-to-b from-transparent to-black/50":
                              !isLoading,
                          },
                        ])}
                      ></div>
                      <PanelsTopLeft
                        className={cn([
                          "absolute bottom-0 right-0 w-4 h-4 mb-2.5 mr-2.5 text-background fill-background/30 dark:text-foreground dark:fill-foreground/30 drop-shadow-[0_3px_2px_rgb(0_0_0_/_70%)] z-20",
                          { "opacity-100": !isLoading },
                          { "opacity-0": isLoading },
                        ])}
                      />
                      {desktopProperties.overlay.gradient == overlay && (
                        <div
                          className={cn([
                            "absolute bottom-0 left-0 z-20 text-xs text-background dark:text-foreground mb-2.5 ml-2.5 font-medium [text-shadow:_0px_2px_7px_rgb(0_0_0)]",
                            { "opacity-100": !isLoading },
                            { "opacity-0": isLoading },
                          ])}
                        >
                          Active
                        </div>
                      )}
                    </div>
                    <div className="mt-2 text-xs text-center">{title}</div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

export default Main;
