import List from "../../components/List";
import Paragraph from "../../components/Paragraph";
import Highlight from "../../components/Highlight";
import Toolbar from "../../components/Toolbar";

function Main() {
  return (
    <div className="flex flex-col w-full h-full">
      <Toolbar />
      <div className="flex flex-col px-4 py-6 overflow-y-auto scrollbar gap-7">
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">Apps Store</div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              The <Highlight>appsStore</Highlight> manages the state for
              applications and their respective windows within the admin
              template. This store allows developers to control application
              lifecycle functions like launching, updating, focusing, and
              closing windows. It is essential for managing multiple apps and
              their interaction with files in the system.
            </Paragraph>
            <Paragraph>Modules and types used:</Paragraph>
            <List>
              <List.Item>
                <List.Content>
                  <Highlight>getFile</Highlight> Utility to retrieve file
                  metadata
                </List.Content>
                <List.Content>
                  <Highlight>generateUniqueId</Highlight> Generates unique IDs
                  for windows
                </List.Content>
                <List.Content>
                  <Highlight>getHighestZIndex</Highlight> Utility to maintain
                  window focus by managing zIndex
                </List.Content>
              </List.Item>
            </List>
            <Paragraph>Types:</Paragraph>
            <List>
              <List.Item>
                <List.Content>
                  <Highlight>ComputedApp</Highlight> Represents an application
                  with metadata about the file associated with it.
                </List.Content>
                <List.Content>
                  <Highlight>Actions</Highlight> Defines actions that can be
                  performed on applications and their windows.
                </List.Content>
              </List.Item>
            </List>
            <Paragraph>Key actions:</Paragraph>
            <List>
              <List.Item>
                <List.Content>
                  <Highlight>selectApps()</Highlight> Returns a list of all
                  active applications, including metadata like file name and
                  path. Filters out any closed or empty applications except the
                  system.
                </List.Content>
                <List.Content>
                  <Highlight>updateApp()</Highlight> Updates properties of a
                  specific app by path.
                </List.Content>
                <List.Content>
                  <Highlight>bringWindowToFront()</Highlight> Adjusts the
                  window's zIndex to bring it to the front, sets focus to true,
                  and un-minimizes it if previously minimized.
                </List.Content>
                <List.Content>
                  <Highlight>launchWindow()</Highlight> Initializes a new window
                  for an application. Assigns unique windowId and sets focus and
                  zIndex.
                </List.Content>
                <List.Content>
                  <Highlight>closeWindow()</Highlight> Closes a specific window,
                  removing it from the application state.
                </List.Content>
                <List.Content>
                  <Highlight>launchApp()</Highlight> Launches an app by path. If
                  a default app exists for a file type, it will open the file in
                  that app. If no app exists, it defaults to the system app.
                </List.Content>
              </List.Item>
            </List>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">Files Store</div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              The <Highlight>filesStore</Highlight> handles the file system
              operations, enabling users to move, copy, delete, and select files
              within the admin template. It plays a crucial role in ensuring
              file operations integrate smoothly with the applications managed
              by <Highlight>appsStore</Highlight>.
            </Paragraph>
            <Paragraph>Modules and types used:</Paragraph>
            <List>
              <List.Item>
                <List.Content>
                  <Highlight>getFile</Highlight> Retrieves file data
                </List.Content>
                <List.Content>
                  <Highlight>generateUniqueId</Highlight> Generates unique IDs
                  for windows
                </List.Content>
                <List.Content>
                  Various utilities for file operations:{" "}
                  <Highlight>moveFile</Highlight>,{" "}
                  <Highlight>copyFile</Highlight>,{" "}
                  <Highlight>deleteFile</Highlight>, etc.
                </List.Content>
              </List.Item>
            </List>
            <Paragraph>Types:</Paragraph>
            <List>
              <List.Item>
                <List.Content>
                  <Highlight>Actions</Highlight> Defines the operations that can
                  be performed on files.
                </List.Content>
              </List.Item>
            </List>
            <Paragraph>Key actions:</Paragraph>
            <List>
              <List.Item>
                <List.Content>
                  <Highlight>selectFile()</Highlight>
                  Retrieves a specific file's metadata by path. Automatically
                  associates files with their default app if available.
                </List.Content>
                <List.Content>
                  <Highlight>moveFile()</Highlight>
                  Moves a file from the origin path to a destination path.
                </List.Content>
                <List.Content>
                  <Highlight>moveAndReplaceFile()</Highlight>
                  Moves and replaces a file at the destination path if a file
                  with the same name exists.
                </List.Content>
                <List.Content>
                  <Highlight>moveAndRenameFile()</Highlight>
                  Moves a file to a new destination path and renames it.
                </List.Content>
                <List.Content>
                  <Highlight>copyFile()</Highlight>
                  Copies a file from the origin path to a destination path.
                </List.Content>
                <List.Content>
                  <Highlight>copyAndReplaceFile()</Highlight>
                  Copies and replaces a file at the destination if a file with
                  the same name exists.
                </List.Content>
                <List.Content>
                  <Highlight>copyAndRenameFile()</Highlight>
                  Copies a file to a destination and renames it if necessary.
                </List.Content>
                <List.Content>
                  <Highlight>deleteFile()</Highlight>
                  Deletes a file from the specified path.
                </List.Content>
                <List.Content>
                  <Highlight>updateFileProperties()</Highlight>
                  Updates specific properties of a file, such as name,
                  extension, or permissions.
                </List.Content>
              </List.Item>
            </List>
            <Paragraph>
              This structure promotes modular, reactive state management and
              ensures your applications and files behave as expected in the
              admin template's environment.
            </Paragraph>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
