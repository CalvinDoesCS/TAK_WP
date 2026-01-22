import { useState } from "react";
import Toolbar from "./components/Toolbar";
import { Calendar } from "@/components/Base/Calendar";
import { Window, ControlButtons } from "@/components/Window";
import { buttonVariants } from "@/components/Base/Button";
import { cn } from "@/lib/utils";

function Main() {
  const [date, setDate] = useState<Date>();

  return (
    <>
      <Window
        x="center"
        y="center"
        width="875"
        height="78%"
        maxWidth="1020"
        maxHeight="90%"
      >
        <ControlButtons className="items-center mt-0 ml-5 h-14" />
        <div className="flex flex-col w-full h-full">
          <Toolbar />
          <Calendar
            mode="single"
            selected={date}
            onSelect={setDate}
            className="h-full p-0"
            classNames={{
              months:
                "h-full flex flex-col sm:flex-row space-y-4 sm:space-x-4 sm:space-y-0",
              month: "w-full h-full flex flex-col",
              caption:
                "flex px-5 pt-5 pb-2 relative bg-muted-foreground/5 items-center",
              caption_label:
                "text-lg font-medium [text-shadow:_0px_2px_7px_rgb(0_0_0_/_10%)] @md/window:text-xl",
              nav: "space-x-1 flex items-center ml-auto",
              nav_button_previous:
                "[&[type=button]]:w-8 [&[type=button]]:h-8 @md/window:[&[type=button]]:w-16 border-muted-foreground/40 [&[type=button]]:bg-background shadow",
              nav_button_next:
                "[&[type=button]]:w-8 [&[type=button]]:h-8 @md/window:[&[type=button]]:w-16 border-muted-foreground/40 [&[type=button]]:bg-background shadow",
              table: "w-full h-full flex-1 border-collapse flex flex-col",
              head: "bg-muted-foreground/5",
              tbody: "flex-1 flex flex-col",
              head_row: "flex",
              head_cell:
                "text-muted-foreground rounded-md w-full py-4 font-normal text-[0.8rem]",
              row: "flex w-full flex-1",
              cell: "border-t border-r h-full w-full text-center text-sm p-0 relative [&:has([aria-selected].day-range-end)]:rounded-r-md [&:has([aria-selected].day-outside)]:bg-accent/50 [&:has([aria-selected])]:bg-accent first:[&:has([aria-selected])]:rounded-l-md last:[&:has([aria-selected])]:rounded-r-md focus-within:relative focus-within:z-20",
              day: cn(
                buttonVariants({ variant: "ghost" }),
                "h-full w-full p-0 font-normal aria-selected:opacity-100"
              ),
            }}
            initialFocus
          />
        </div>
      </Window>
    </>
  );
}

export default Main;
