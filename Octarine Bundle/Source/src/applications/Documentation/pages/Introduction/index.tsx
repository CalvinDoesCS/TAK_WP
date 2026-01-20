import Toolbar from "../../components/Toolbar";
import List from "../../components/List";
import Paragraph from "../../components/Paragraph";
import RadixLogo from "@/assets/images/logos/radix-ui.svg";
import ReactRouterLogo from "@/assets/images/logos/react-router.svg";
import ReactLogo from "@/assets/images/logos/react.svg";
import TailwindLogo from "@/assets/images/logos/tailwind.svg";
import TypescriptLogo from "@/assets/images/logos/typescript.svg";
import ViteLogo from "@/assets/images/logos/vite.svg";
import ZustandLogo from "@/assets/images/logos/zustand.png";

function Main() {
  return (
    <div className="flex flex-col w-full h-full">
      <Toolbar />
      <div className="flex flex-col px-4 py-6 overflow-y-auto scrollbar gap-7">
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">What is Octarine?</div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              Octarine is a web desktop template designed to replicate the look
              and feel of a full operating system. It incorporates a range of
              OS-inspired features to provide an immersive desktop-like
              experience within the browser.
            </Paragraph>
            <Paragraph>Key features of Octarine include:</Paragraph>
            <List>
              <List.Item>
                <List.Title>Dock and Start Menu</List.Title>
                <List.Content>
                  Inspired by macOS and Windows, Octarine offers a dock feature
                  for pinning apps and a start menu to quickly access
                  applications, creating a seamless experience for managing and
                  launching tools.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Desktop Environment</List.Title>
                <List.Content>
                  Octarine includes a desktop where users can place files and
                  folders and perform typical file operations such as cut, copy,
                  and rename.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Drag-and-Drop File Management</List.Title>
                <List.Content>
                  Users can drag and drop files to organize their workspace.
                  Octarine also includes file validation to prevent accidental
                  overwrites by prompting users when duplicate files are
                  detected in the target directory.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Date and Time Widget</List.Title>
                <List.Content>
                  A built-in widget displays the current day and time, adding
                  aesthetic appeal and practical utility to the desktop
                  interface.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Customizable Wallpaper and Overlay</List.Title>
                <List.Content>
                  Octarine allows users to personalize their workspace with
                  custom wallpapers and overlay options, making it an ideal
                  starter project for UI and design enthusiasts.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Radix UI Components</List.Title>
                <List.Content>
                  Most of the Octarine interface components are built using
                  Radix UI, ensuring a polished and consistent design throughout
                  the template.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Light and Dark Modes</List.Title>
                <List.Content>
                  Users can toggle between light and dark themes for a
                  personalized experience that adapts to different environments.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Built-in Demo Applications</List.Title>
                <List.Content>
                  Octarine comes preloaded with demo applications like
                  Calculator, Calendar, Mail, Notes, Bug Report, and Clock.
                  While these are non-functional, they showcase Octarine’s
                  versatility as a desktop environment.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>File Manager</List.Title>
                <List.Content>
                  The file manager enables users to view the full directory
                  structure and perform file operations, enhancing productivity
                  by providing an organized view of files and folders.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Multi-Window Support</List.Title>
                <List.Content>
                  Octarine supports multiple windows, allowing users to manage
                  and work on several tasks simultaneously, just like in a
                  standard OS environment.
                </List.Content>
              </List.Item>
            </List>
            <Paragraph>
              Octarine is an ideal choice for developers looking to start a
              project with a visually rich and interactive desktop environment,
              providing an engaging experience that closely mirrors a
              traditional operating system.
            </Paragraph>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">Core Library Used</div>
          </div>
          <div className="grid grid-cols-2 gap-3.5 @lg/content:grid-cols-4">
            <a
              href="https://react.dev/"
              target="_blank"
              className="border rounded-md bg-muted-foreground/[.01] shadow-sm p-4 flex flex-col items-center justify-center gap-2 hover:border-primary"
            >
              <div className="flex items-center h-28">
                <img src={RadixLogo} className="w-20" />
              </div>
              <div className="font-medium">React</div>
            </a>
            <a
              href="https://vite.dev/"
              target="_blank"
              className="border rounded-md bg-muted-foreground/[.01] shadow-sm p-4 flex flex-col items-center justify-center gap-2 hover:border-primary"
            >
              <div className="flex items-center h-28">
                <img src={ViteLogo} className="w-20" />
              </div>
              <div className="font-medium">Vite</div>
            </a>
            <a
              href="https://www.typescriptlang.org/"
              target="_blank"
              className="border rounded-md bg-muted-foreground/[.01] shadow-sm p-4 flex flex-col items-center justify-center gap-2 hover:border-primary"
            >
              <div className="flex items-center h-28">
                <img src={ReactLogo} className="w-20" />
              </div>
              <div className="font-medium">Typescript</div>
            </a>
            <a
              href="https://tailwindcss.com/"
              target="_blank"
              className="border rounded-md bg-muted-foreground/[.01] shadow-sm p-4 flex flex-col items-center justify-center gap-2 hover:border-primary"
            >
              <div className="flex items-center h-28">
                <img src={TailwindLogo} className="w-20" />
              </div>
              <div className="font-medium">Tailwind</div>
            </a>
            <a
              href="https://www.radix-ui.com/"
              target="_blank"
              className="border rounded-md bg-muted-foreground/[.01] shadow-sm p-4 flex flex-col items-center justify-center gap-2 hover:border-primary"
            >
              <div className="flex items-center h-28">
                <img src={TypescriptLogo} className="w-20" />
              </div>
              <div className="font-medium">Radix</div>
            </a>
            <a
              href="https://reactrouter.com/en/main"
              target="_blank"
              className="border rounded-md bg-muted-foreground/[.01] shadow-sm p-4 flex flex-col items-center justify-center gap-2 hover:border-primary"
            >
              <div className="flex items-center h-28">
                <img src={ReactRouterLogo} className="w-20" />
              </div>
              <div className="font-medium">React Router</div>
            </a>
            <a
              href="https://zustand.docs.pmnd.rs/getting-started/introduction"
              target="_blank"
              className="border rounded-md bg-muted-foreground/[.01] shadow-sm p-4 flex flex-col items-center justify-center gap-2 hover:border-primary"
            >
              <div className="flex items-center h-28">
                <img src={ZustandLogo} className="w-20" />
              </div>
              <div className="font-medium">Zustand</div>
            </a>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
