import List from "../../components/List";
import PreviewCode from "../../components/PreviewCode";
import Highlight from "../../components/Highlight";
import Paragraph from "../../components/Paragraph";
import Toolbar from "../../components/Toolbar";

function Main() {
  return (
    <div className="flex flex-col w-full h-full">
      <Toolbar />
      <div className="flex flex-col px-4 py-6 overflow-y-auto scrollbar gap-7">
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">
              Creating the Application Component
            </div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              The first step to register an application is to create the
              component that will serve as the application. This component is
              placed in the <Highlight>/src/applications/[app_name]</Highlight>{" "}
              folder. For example, if the application name is{" "}
              <Highlight>MyApp</Highlight>, create a{" "}
              <Highlight>Main.tsx</Highlight> file inside the
              <Highlight>/src/applications/MyApp/</Highlight> folder.
            </Paragraph>
            <Paragraph>Example Code for Main Component:</Paragraph>
            <PreviewCode>{`import { MemoryRouter } from "react-router-dom";
import { Window, ControlButtons } from "@/components/Window";
import Router from "./router";

function Main() {
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
        <ControlButtons className="mt-5" />
        <MemoryRouter>
          <Router />
        </MemoryRouter>
      </Window>
    </>
  );
}

export default Main;`}</PreviewCode>
            <Paragraph>In this code:</Paragraph>
            <List>
              <List.Item>
                <List.Title>Window</List.Title>
                <List.Content>
                  Provides the application area with features like dragging,
                  resizing, minimizing, and closing.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>ControlButtons</List.Title>
                <List.Content>
                  Adds additional window controls, like close and minimize
                  buttons.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>MemoryRouter</List.Title>
                <List.Content>
                  Allows internal navigation within the app without reloading
                  the page.
                </List.Content>
              </List.Item>
            </List>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">
              Adding the Application File to <Highlight>filesStore</Highlight>
            </div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              After creating the application component, the next step is to add
              the application file to <Highlight>filesStore</Highlight>. The{" "}
              <Highlight>filesStore</Highlight> holds global file data, allowing
              access to and manipulation of files across the application.
            </Paragraph>
            <Paragraph>
              Add the application file information in filesStore as shown below:
            </Paragraph>
            <PreviewCode>{`import { writable } from "svelte/store";

export const filesStore = writable({
  "uniqueFileId": {
    id: "uniqueFileId",
    name: "MyApp.exe",
    path: "/applications/MyApp",
    icon: "/icons/myAppIcon.png",
    type: "application",
    extension: ".exe",
    opened: false,
  },
  // Add more files as needed
});
`}</PreviewCode>
            <Paragraph>Property Descriptions:</Paragraph>
            <List>
              <List.Item>
                <List.Content>
                  <Highlight>id</Highlight> Unique ID for each file.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Content>
                  <Highlight>name</Highlight> File name for the application.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Content>
                  <Highlight>path</Highlight> File path in the system.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Content>
                  <Highlight>icon</Highlight> Path to the file icon.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Content>
                  <Highlight>type</Highlight> File type, in this case
                  application.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Content>
                  <Highlight>extension</Highlight> File extension, such as .exe
                  for an application.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Content>
                  <Highlight>opened</Highlight> Status indicating if the file is
                  currently open.
                </List.Content>
              </List.Item>
            </List>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">
              Registering the Application in <Highlight>appsStore</Highlight>
            </div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              The final step is to add an entry for the application in
              <Highlight>appsStore</Highlight>, which stores detailed
              application information like name, path, category, and the
              component to be rendered within the <Highlight>Window</Highlight>.
            </Paragraph>
            <Paragraph>
              Open <Highlight>appsStore.ts</Highlight> and add a new entry for
              the application:
            </Paragraph>
            <PreviewCode>{`import { AppRegistry } from "@/types";

export const appsRegistry: AppRegistry = {
  "MyApp": {
    name: "My Application",
    fileName: "MyApp",
    path: "/applications/MyApp",
    component: "MyAppComponent",
    category: "Utilities",
    supportedFileExtensions: [".exe"],
    file: {
      id: "uniqueFileId",
      icon: "/icons/myAppIcon.png",
      selected: false,
      type: "application",
      extension: ".exe",
    },
  },
  // Add other applications as needed
};
`}</PreviewCode>
            <Paragraph>Property Descriptions:</Paragraph>
            <List>
              <List.Item>
                <List.Content>
                  <Highlight>name</Highlight> Display name for the application.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Content>
                  <Highlight>fileName</Highlight> The name of the application
                  file.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Content>
                  <Highlight>path</Highlight> The application path.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Content>
                  <Highlight>component</Highlight> The name of the component to
                  be rendered in the Window.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Content>
                  <Highlight>category</Highlight> The category of the
                  application, such as "Utilities."
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Content>
                  <Highlight>supportedFileExtensions</Highlight> Supported file
                  extensions.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Content>
                  <Highlight>file</Highlight> File details of the application.
                </List.Content>
              </List.Item>
            </List>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">Window Context</div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              The <Highlight>Window</Highlight> component uses{" "}
              <Highlight>windowContext</Highlight> to provide access to the
              context of the currently open application, including information
              on the currently accessed file.
            </Paragraph>
            <Paragraph>
              <Highlight>windowContext</Highlight> Example Code:
            </Paragraph>
            <PreviewCode>{`import { createContext } from "react";
import { type ComputedApp, type Window } from "@/stores/appsStore";

export interface WindowContextInterface {
  scoped: boolean;
  app: ComputedApp;
  window: Window & {
    index: string;
  };
}

const windowContext = createContext<WindowContextInterface>({
  scoped: false,
  app: {
    fileName: "",
    path: "",
    pinned: false,
    loading: false,
    supportedFileExtensions: [],
    category: "Media",
    windows: {},
    file: {
      id: "",
      icon: "",
      selected: false,
      animated: false,
      editable: false,
      index: 0,
      type: "directory",
      extension: "",
      component: "",
      entries: {},
    },
  },
  window: {
    index: "",
    minimize: false,
    zoom: false,
    focus: false,
    zIndex: 0,
  },
});

export { windowContext };
`}</PreviewCode>
            <Paragraph>
              The <Highlight>windowContext</Highlight> provides a specialized
              context for the <Highlight>Window</Highlight> component,
              including:
            </Paragraph>
            <List>
              <List.Item>
                <List.Content>
                  <Highlight>scoped</Highlight> Specifies whether the
                  application is within a certain scope.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Content>
                  <Highlight>app</Highlight> Information about the currently
                  open application.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Content>
                  <Highlight>window</Highlight> Window status details, like
                  <Highlight>minimize</Highlight>, <Highlight>zoom</Highlight>,
                  and <Highlight>focus</Highlight>.
                </List.Content>
              </List.Item>
            </List>
            <Paragraph>
              By following these steps, you can successfully register and use an
              application within a system that supports a windowed interface,
              complete with drag, resize, and minimize capabilities via the
              Window component.
            </Paragraph>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
