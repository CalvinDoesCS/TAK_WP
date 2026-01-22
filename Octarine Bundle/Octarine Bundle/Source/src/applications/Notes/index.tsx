import { Window, ControlButtons } from "@/components/Window";
import {
  ResizableHandle,
  ResizablePanel,
  ResizablePanelGroup,
} from "@/components/Base/Resizable";
import { SidebarProvider } from "@/components/Base/Sidebar";
import { Textarea } from "@/components/Base/Textarea";
import { DraggableHandle } from "@/components/Window";
import SideMenu from "./components/SideMenu";
import Toolbar from "./components/Toolbar";
import { useState } from "react";

function Main() {
  const [notes, setNotes] = useState(
    "Reviewed new messages from the client team regarding the project timeline and deliverables. Noted questions about specific functionality and design adjustments for the upcoming sprint."
  );

  return (
    <>
      <Window
        x="center"
        y="center"
        width="85%"
        height="80%"
        maxWidth="90%"
        maxHeight="90%"
      >
        <ControlButtons className="mt-5" />
        <SidebarProvider className="h-full min-h-0">
          <ResizablePanelGroup direction="horizontal">
            <ResizablePanel
              className="hidden @lg/window:block max-w-72 min-w-60 relative"
              defaultSize={30}
              minSize={30}
              maxSize={35}
            >
              <DraggableHandle className="absolute inset-x-0 top-0 w-full h-14" />
              <div className="h-full pt-14">
                <div className="relative h-full">
                  <SideMenu />
                </div>
              </div>
            </ResizablePanel>
            <ResizableHandle withHandle className="hidden @lg/window:flex" />
            <ResizablePanel className="z-50 shadow-xl bg-background/70">
              <div className="flex flex-col h-full">
                <Toolbar />
                <Textarea
                  className="w-full h-full p-6 text-lg border-0 resize-none focus-visible:ring-transparent focus-visible:ring-offset-0 placeholder:text-muted-foreground/70"
                  value={notes}
                  placeholder="Type your notes here."
                  onChange={(el) => setNotes(el.target.value)}
                />
              </div>
            </ResizablePanel>
          </ResizablePanelGroup>
        </SidebarProvider>
      </Window>
    </>
  );
}

export default Main;
