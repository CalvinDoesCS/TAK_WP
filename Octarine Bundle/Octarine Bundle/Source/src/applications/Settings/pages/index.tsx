import { Outlet } from "react-router-dom";
import {
  ResizableHandle,
  ResizablePanel,
  ResizablePanelGroup,
} from "@/components/Base/Resizable";
import { SidebarProvider } from "@/components/Base/Sidebar";
import { DraggableHandle } from "@/components/Window";
import SideMenu from "../components/SideMenu";

function Main() {
  return (
    <SidebarProvider className="h-full min-h-0">
      <ResizablePanelGroup direction="horizontal">
        <ResizablePanel
          className="hidden @lg/window:block max-w-64 min-w-60 relative"
          defaultSize={18}
          minSize={18}
        >
          <DraggableHandle className="absolute inset-x-0 top-0 w-full h-14" />
          <div className="h-full pt-14">
            <div className="relative h-full">
              <SideMenu />
            </div>
          </div>
        </ResizablePanel>
        <ResizableHandle withHandle className="hidden @lg/window:flex" />
        <ResizablePanel className="@container/content z-50 shadow-xl bg-background/70">
          <Outlet />
        </ResizablePanel>
      </ResizablePanelGroup>
    </SidebarProvider>
  );
}

export default Main;
