import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/Base/Table";
import { Checkbox } from "@/components/Base/Checkbox";
import { Button } from "@/components/Base/Button";
import Toolbar from "../../components/Toolbar";
import _ from "lodash";

function Main() {
  const keyboardShortcuts = [
    {
      description: "Change the way Tab moves focus",
      key: "^F7",
    },
    {
      description: "Show Desktop",
      key: "F11",
    },
    {
      description: "Open Spotlight Search",
      key: "Cmd + Space",
    },
    {
      description: "Take a Screenshot",
      key: "Cmd + Shift + 4",
    },
    {
      description: "Switch to Next Application",
      key: "Cmd + Tab",
    },
    {
      description: "Force Quit Application",
      key: "Cmd + Option + Esc",
    },
    {
      description: "Open Mission Control",
      key: "Ctrl + Up Arrow",
    },
    {
      description: "Minimize Window",
      key: "Cmd + M",
    },
    {
      description: "Close Window",
      key: "Cmd + W",
    },
    {
      description: "Lock Screen",
      key: "Ctrl + Cmd + Q",
    },
  ];

  return (
    <div className="flex flex-col w-full h-full">
      <Toolbar />
      <div className="flex flex-col px-4 py-6 overflow-y-auto scrollbar gap-7">
        <div className="flex flex-col gap-4">
          <div className="px-3 mb-2">
            <div className="font-medium">Keyboard Shortcut Center</div>
            <div className="mt-1 mb-0.5 text-xs leading-normal text-muted-foreground text-justify">
              The Keyboard Shortcut Center provides easy access to your shortcut
              settings, located in the top-right corner of your screen. You can
              open or close the Keyboard Shortcut Center by clicking the
              keyboard icon in the menu bar.
            </div>
          </div>
          <div className="flex flex-col border divide-y rounded-md bg-muted-foreground/[.01] shadow-sm">
            <Table>
              <TableHeader>
                <TableRow>
                  <TableHead className="py-3.5"></TableHead>
                  <TableHead className="py-3.5">Action</TableHead>
                  <TableHead className="py-3.5 text-right">Key</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {keyboardShortcuts.map(
                  (keyboardShortcut, keyboardShortcutKey) => (
                    <TableRow key={keyboardShortcutKey}>
                      <TableCell className="py-3.5">
                        <Checkbox
                          id={keyboardShortcutKey.toString()}
                          checked={!_.random(0, 1)}
                        />
                      </TableCell>
                      <TableCell className="py-3.5">
                        {keyboardShortcut.description}
                      </TableCell>
                      <TableCell className="py-3.5 flex justify-end">
                        <div className="text-xs font-medium rounded-md bg-muted-foreground text-background px-2 py-0.5">
                          {keyboardShortcut.key}
                        </div>
                      </TableCell>
                    </TableRow>
                  )
                )}
              </TableBody>
            </Table>
          </div>
          <div className="flex -mt-1.5 mb-5 gap-2">
            <Button size="sm">Restore Defaults</Button>
            <Button size="sm" className="ml-auto">
              New Shortcut
            </Button>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
