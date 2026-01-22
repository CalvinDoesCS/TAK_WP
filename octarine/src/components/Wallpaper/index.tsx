import { useDesktopPropertiesStore } from "@/stores/desktopPropertiesStore";
import { useEffect, useState } from "react";
import { cn } from "@/lib/utils";

function Main({ showTime = true }: { showTime?: boolean }) {
  const { desktopProperties } = useDesktopPropertiesStore();
  const imageAssets = import.meta.glob<{
    default: string;
  }>("/src/assets/images/wallpapers/**/*.{jpg,jpeg,png,svg}", { eager: true });

  // State to store current date and time
  const [dateTime, setDateTime] = useState({
    day: "",
    date: "",
    time: "",
  });

  // Function to update date and time
  useEffect(() => {
    const updateDateTime = () => {
      const now = new Date();
      const dayNames = [
        "SUNDAY",
        "MONDAY",
        "TUESDAY",
        "WEDNESDAY",
        "THURSDAY",
        "FRIDAY",
        "SATURDAY",
      ];
      const monthNames = [
        "JANUARY",
        "FEBRUARY",
        "MARCH",
        "APRIL",
        "MAY",
        "JUNE",
        "JULY",
        "AUGUST",
        "SEPTEMBER",
        "OCTOBER",
        "NOVEMBER",
        "DECEMBER",
      ];

      const day = dayNames[now.getDay()];
      const date = `${now.getDate()} ${
        monthNames[now.getMonth()]
      } ${now.getFullYear()}`;
      const time = now.toLocaleTimeString("en-US", {
        hour: "2-digit",
        minute: "2-digit",
      });

      setDateTime({ day, date, time });
    };

    // Initial update and setting an interval to update every minute
    updateDateTime();
    const interval = setInterval(updateDateTime, 1000); // Update every minute

    return () => clearInterval(interval);
  }, []);

  return (
    <div
      className="absolute inset-0"
      style={{
        backgroundColor: desktopProperties.wallpaper.backgroundColor,
      }}
    >
      {desktopProperties.wallpaper.isShow && (
        <img
          className={cn([
            "absolute object-cover size-full",
            { grayscale: desktopProperties.wallpaper.grayscale },
          ])}
          style={{
            opacity: desktopProperties.wallpaper.opacity,
          }}
          src={
            imageAssets[
              "/src/assets/images/wallpapers/" +
                desktopProperties.wallpaper.imageName
            ].default
          }
        />
      )}
      <div className="absolute inset-0 flex items-center justify-center">
        {desktopProperties.overlay.isShow && (
          <div
            className={cn([
              "absolute inset-0 bg-gradient-to-bl",
              desktopProperties.overlay.gradient,
            ])}
            style={{
              opacity: desktopProperties.overlay.opacity,
            }}
          ></div>
        )}
        {showTime && (
          <div className="flex flex-col items-center font-medium text-background dark:text-foreground opacity-40">
            <div className="text-4xl sm:text-7xl lg:text-8xl font-orbitron">
              {dateTime.day}
            </div>
            <div className="mt-2 text-sm sm:mt-5 sm:text-xl lg:text-2xl font-orbitron">
              {dateTime.date}
            </div>
            <div className="mt-1 text-sm sm:text-xl lg:text-2xl font-orbitron">
              - {dateTime.time} -
            </div>
          </div>
        )}
      </div>
    </div>
  );
}

export default Main;
