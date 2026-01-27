import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/Base/Tabs";
import React, { useEffect, useState } from "react";
import { AlarmClock, AlarmClockPlus, Timer, TimerReset } from "lucide-react";
import { Window, DraggableHandle, ControlButtons } from "@/components/Window";

function Main() {
  const [time, setTime] = useState(new Date());

  // Update time every second
  useEffect(() => {
    const interval = setInterval(() => {
      setTime(new Date());
    }, 1000);
    return () => clearInterval(interval);
  }, []);

  // Calculate rotation degrees for each hand
  const secondsDegrees = (time.getSeconds() / 60) * 360;
  const minutesDegrees = (time.getMinutes() / 60) * 360 + secondsDegrees / 60;
  const hoursDegrees = (time.getHours() / 12) * 360 + minutesDegrees / 12;

  // Format the current date
  const formattedDate = new Intl.DateTimeFormat("en-US", {
    weekday: "long",
    month: "short",
    day: "numeric",
  }).format(time);

  // Get the time zone
  const timeZone = Intl.DateTimeFormat().resolvedOptions().timeZone;

  return (
    <>
      <Window
        x="center"
        y="10%"
        width={550}
        height={460}
        maxWidth={600}
        maxHeight={600}
      >
        <ControlButtons className="mt-2.5 ml-3" />
        <DraggableHandle className="absolute w-full h-10" />
        <Tabs defaultValue="world-clock" className="flex flex-col h-full">
          <div className="flex-1 pt-10 shadow-sm bg-background">
            <TabsContent
              value="world-clock"
              className="flex items-center justify-center h-full -mt-5"
            >
              <div>
                <div className="relative flex items-center justify-center w-48 h-48 border-8 rounded-full bg-muted-foreground/20 border-foreground/70">
                  {/* Hour Hand */}
                  <div
                    className="absolute bottom-1/2 w-1.5 h-10 bg-foreground/50 origin-bottom transition-transform duration-100 ease-in-out"
                    style={{ transform: `rotate(${hoursDegrees}deg)` }}
                  />
                  {/* Minute Hand */}
                  <div
                    className="absolute w-1 h-16 transition-transform duration-100 ease-in-out origin-bottom bg-foreground/50 bottom-1/2"
                    style={{ transform: `rotate(${minutesDegrees}deg)` }}
                  />
                  {/* Second Hand */}
                  <div
                    className="absolute bottom-1/2 w-0.5 h-20 bg-foreground origin-bottom transition-transform duration-100 ease-in-out"
                    style={{ transform: `rotate(${secondsDegrees}deg)` }}
                  />
                  {/* Center Circle */}
                  <div className="absolute w-3 h-3 rounded-full bg-foreground/50" />
                </div>
                <div className="flex flex-col gap-1 mt-5 text-center">
                  <div className="text-muted-foreground">{timeZone}</div>
                  <div className="text-base font-medium">{formattedDate}</div>
                </div>
              </div>
            </TabsContent>
          </div>
          <TabsList className="hidden h-auto grid-cols-4 @md/window:grid bg-transparent rounded-none p-3 gap-3">
            <TabsTrigger
              className="flex flex-col gap-2 py-4 text-xs data-[state=active]:bg-foreground/[.06] rounded-lg focus:ring-transparent focus:ring-offset-0"
              value="alarm"
            >
              <AlarmClockPlus className="w-8 h-8 [&.lucide]:stroke-1" />
              <div className="opacity-80">Alarm</div>
            </TabsTrigger>
            <TabsTrigger
              className="flex flex-col gap-2 py-3 text-xs data-[state=active]:bg-foreground/[.06] rounded-lg focus:ring-transparent focus:ring-offset-0"
              value="world-clock"
            >
              <AlarmClock className="w-8 h-8 [&.lucide]:stroke-1" />
              <div className="opacity-80">World Clock</div>
            </TabsTrigger>
            <TabsTrigger
              className="flex flex-col gap-2 py-3 text-xs data-[state=active]:bg-foreground/[.06] rounded-lg focus:ring-transparent focus:ring-offset-0"
              value="stop-watch"
            >
              <TimerReset className="w-8 h-8 [&.lucide]:stroke-1" />
              <div className="opacity-80">Stop Watch</div>
            </TabsTrigger>
            <TabsTrigger
              className="flex flex-col gap-2 py-3 text-xs data-[state=active]:bg-foreground/[.06] rounded-lg focus:ring-transparent focus:ring-offset-0"
              value="timer"
            >
              <Timer className="w-8 h-8 [&.lucide]:stroke-1" />
              <div className="opacity-80">Timer</div>
            </TabsTrigger>
          </TabsList>
        </Tabs>
      </Window>
    </>
  );
}

export default Main;
