import List from "../../components/List";
import Link from "../../components/Link";
import Highlight from "../../components/Highlight";
import Toolbar from "../../components/Toolbar";

function Main() {
  return (
    <div className="flex flex-col w-full h-full">
      <Toolbar />
      <div className="flex flex-col px-4 py-6 overflow-y-auto scrollbar gap-7">
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">
              Configuration & Customization
            </div>
          </div>
          <div className="flex flex-col gap-3">
            <List>
              <List.Item>
                <List.Title>Tailwind Setup</List.Title>
                <List.Content>
                  Tailwind is pre-configured in this project. Key configurations
                  are located in the <Highlight>tailwind.config.js</Highlight>{" "}
                  file, where you can adjust theme settings, extend the color
                  palette, and customize spacing, typography, and breakpoints to
                  fit your design needs.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Theme Customization</List.Title>
                <List.Content>
                  This template provides a customizable theme with options for
                  colors, font sizes, and responsive breakpoints. Modify these
                  directly in the <Highlight>theme</Highlight> section within{" "}
                  <Highlight>tailwind.config.js</Highlight> for quick,
                  project-wide changes.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Dark Mode</List.Title>
                <List.Content>
                  The template includes support for dark mode. You can toggle it
                  by modifying the <Highlight>darkMode</Highlight> setting in{" "}
                  <Highlight>tailwind.config.js</Highlight> or by adding a dark
                  class to your HTML or component root.
                </List.Content>
              </List.Item>
            </List>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">Usage in Components</div>
          </div>
          <div className="flex flex-col gap-3">
            <List>
              <List.Item>
                <List.Title>Applying Tailwind Classes</List.Title>
                <List.Content>
                  Use Tailwind utility classes directly in your React components
                  for fast, consistent styling. For conditional classes,
                  consider using <Highlight>classnames</Highlight> to simplify
                  conditional styling logic.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Responsive Design</List.Title>
                <List.Content>
                  Tailwind’s responsive utilities are enabled, allowing you to
                  adapt layouts across devices by prefixing classes with
                  breakpoints (e.g., <Highlight>md:</Highlight>,{" "}
                  <Highlight>lg:</Highlight>
                  ).
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Optimizing Production Builds</List.Title>
                <List.Content>
                  Unused Tailwind styles are purged automatically for production
                  builds. Simply run <Highlight>npm run build</Highlight> to
                  generate an optimized CSS bundle, ensuring only the necessary
                  styles are included in the final output.
                </List.Content>
              </List.Item>
            </List>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">IDE Recommendations</div>
          </div>
          <div className="flex flex-col gap-3">
            <List>
              <List.Item>
                <List.Title>Visual Studio Code (VS Code)</List.Title>
                <List.Content>
                  Highly recommended for Tailwind projects. With the{" "}
                  <Link
                    href="https://marketplace.visualstudio.com/items?itemName=bradlc.vscode-tailwindcss"
                    target="_blank"
                  >
                    Tailwind CSS IntelliSense
                  </Link>{" "}
                  extension, VS Code offers real-time autocomplete, syntax
                  highlighting, and error checking for Tailwind classes. It also
                  provides hover previews for utility classes and suggestions,
                  making it easier to write and edit Tailwind code.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>WebStorm</List.Title>
                <List.Content>
                  Another robust IDE that supports Tailwind well. WebStorm
                  provides powerful code completion and has a similar plugin,{" "}
                  <Link
                    href="https://plugins.jetbrains.com/plugin/14992-tailwind-css"
                    target="_blank"
                  >
                    Tailwind CSS Support
                  </Link>
                  , which offers class autocompletion and documentation links.
                  WebStorm’s advanced project navigation and built-in terminal
                  make it suitable for larger Tailwind projects.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Sublime Text</List.Title>
                <List.Content>
                  If you prefer a lightweight editor, Sublime Text can be
                  enhanced with the{" "}
                  <Link
                    href="https://packagecontrol.io/packages/Tailwind%20CSS%20Autocomplete"
                    target="_blank"
                  >
                    Tailwind CSS Autocomplete
                  </Link>{" "}
                  plugin to provide class suggestions. It’s fast and efficient,
                  though it may lack some of the more advanced IDE features of
                  VS Code and WebStorm.
                </List.Content>
              </List.Item>
            </List>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
