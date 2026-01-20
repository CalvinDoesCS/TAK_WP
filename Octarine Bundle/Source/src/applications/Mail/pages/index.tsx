import { Outlet } from "react-router-dom";
import { useState } from "react";
import { Inbox } from "lucide-react";
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/Base/Select";
import {
  ResizableHandle,
  ResizablePanel,
  ResizablePanelGroup,
} from "@/components/Base/Resizable";
import { SidebarProvider } from "@/components/Base/Sidebar";
import { DraggableHandle } from "@/components/Window";
import SideMenu from "../components/SideMenu";

function Main() {
  const accounts = [
    {
      value: "ethanhunt@left4code.com",
      label: "Ethan Hunt",
    },
    {
      value: "lioraashford@left4code.com",
      label: "Liora Ashford",
    },
    {
      value: "kaithorne@left4code.com",
      label: "Kai Thorne",
    },
  ];

  const [value, setValue] = useState("ethanhunt@left4code.com");

  return (
    <SidebarProvider className="h-full min-h-0">
      <ResizablePanelGroup direction="horizontal">
        <ResizablePanel
          className="hidden @lg/window:block max-w-64 min-w-60 relative"
          defaultSize={20}
          minSize={20}
          maxSize={25}
        >
          <DraggableHandle className="absolute inset-x-0 top-0 w-full h-14" />
          <div className="px-4 pt-14">
            <Select value={value} onValueChange={(value) => setValue(value)}>
              <SelectTrigger className="relative pl-10 focus:ring-transparent focus:ring-offset-0">
                <Inbox className="absolute inset-y-0 left-0 w-4 h-4 my-auto ml-3" />
                <SelectValue placeholder="Select a user" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  {accounts.map((account, accountKey) => (
                    <SelectItem
                      value={account.value}
                      className="flex"
                      key={accountKey}
                    >
                      {account.label}
                    </SelectItem>
                  ))}
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>
          <div className="h-full pt-5">
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
