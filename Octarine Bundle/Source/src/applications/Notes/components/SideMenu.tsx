import { cn } from "@/lib/utils";
import {
  Sidebar,
  SidebarContent,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from "@/components/Base/Sidebar";

function Main() {
  const notes = [
    {
      title: "Today",
      notes: [
        {
          title: "Meeting Follow-Up",
          content: "Summarized key points from today's client meeting.",
          time: "09:30 AM",
        },
        {
          title: "Blog Ideas",
          content: "Outline for potential blog topics to explore.",
          time: "11:15 AM",
        },
        {
          title: "Project Update",
          content: "Sent revised timeline to the project manager.",
          time: "01:45 PM",
        },
        {
          title: "Subscription Renewal",
          content: "Reminder to renew software subscription.",
          time: "03:00 PM",
        },
        {
          title: "Deleted Items",
          content: "Cleared outdated documents from workspace.",
          time: "04:35 PM",
        },
        {
          title: "Archived Notes",
          content: "Saved last quarter's reports for reference.",
          time: "06:20 PM",
        },
      ],
    },
    {
      title: "Yesterday",
      notes: [
        {
          title: "Event RSVP",
          content: "Confirmed attendance for upcoming networking event.",
          time: "10:15 AM",
        },
        {
          title: "Software Update",
          content: "Downloaded latest patch for design software.",
          time: "12:45 PM",
        },
        {
          title: "Community Forum",
          content: "Shared insights on a web development discussion thread.",
          time: "02:30 PM",
        },
        {
          title: "Office Supplies Order",
          content: "Ordered paper and printer cartridges.",
          time: "05:10 PM",
        },
        {
          title: "Special Offer",
          content: "Promotion on design tools expiring soon.",
          time: "07:55 PM",
        },
      ],
    },
  ];

  return (
    <>
      <Sidebar className="absolute w-full h-full [&>[data-sidebar]]:bg-transparent group-data-[side=left]:border-r-0">
        <SidebarContent className="px-2">
          {notes.map((note, noteKey) => {
            return (
              <SidebarGroup key={noteKey} className="first:-mt-3 last:mb-3">
                <SidebarGroupLabel>{note.title}</SidebarGroupLabel>
                <SidebarGroupContent>
                  <SidebarMenu>
                    {note.notes.map((noteItem, noteItemKey) => (
                      <SidebarMenuItem key={noteItemKey}>
                        <SidebarMenuButton asChild>
                          <a
                            href=""
                            className={cn([
                              "px-4 py-2 h-auto hover:bg-foreground/[.03] [&.selected>svg]:stroke-[1.7] [&.selected]:font-medium [&.selected]:bg-foreground/[.06]",
                              {
                                selected: !noteKey && !noteItemKey,
                              },
                            ])}
                          >
                            <div className="w-full">
                              <div className="items-center justify-between @4xl/window:flex">
                                <div className="font-medium">
                                  {noteItem.title}
                                </div>
                                <div className="text-xs text-muted-foreground/80 mt-1 @4xl/window:mt-0">
                                  {noteItem.time}
                                </div>
                              </div>
                              <div className="mt-1 mb-1.5 line-clamp-2 text-muted-foreground/80">
                                <div className="max-w-full truncate">
                                  {noteItem.content}
                                </div>
                              </div>
                            </div>
                          </a>
                        </SidebarMenuButton>
                      </SidebarMenuItem>
                    ))}
                  </SidebarMenu>
                </SidebarGroupContent>
              </SidebarGroup>
            );
          })}
        </SidebarContent>
      </Sidebar>
    </>
  );
}

export default Main;
