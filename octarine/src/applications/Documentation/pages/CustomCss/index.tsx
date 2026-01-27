import List from "../../components/List";
import Link from "../../components/Link";
import Paragraph from "../../components/Paragraph";
import Highlight from "../../components/Highlight";
import PreviewCode from "../../components/PreviewCode";
import Toolbar from "../../components/Toolbar";

function Main() {
  return (
    <div className="flex flex-col w-full h-full">
      <Toolbar />
      <div className="flex flex-col px-4 py-6 overflow-y-auto scrollbar gap-7">
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">
              Additional Custom CSS in This Project
            </div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              Although this project primarily uses Tailwind CSS, some additional
              CSS is defined in <Highlight>"/assets/css/*"</Highlight>. This
              custom CSS file includes:
            </Paragraph>
            <List>
              <List.Item>
                <List.Title>Global Theme Variables</List.Title>
                <List.Content>
                  In <Highlight>"/assets/css/app.css"</Highlight>, color
                  variables for the overall UI are defined within the{" "}
                  <Highlight>:root</Highlight> and
                  <Highlight>.dark</Highlight> selectors. These variables
                  control colors across both light and dark themes, ensuring
                  consistent theming throughout the application. These variables
                  allow you to fine-tune the color scheme of your project
                  quickly without diving into every component. For theming with
                  ShadCN UI, refer to the{" "}
                  <Link
                    href="https://ui.shadcn.com/docs/theming"
                    target="_blank"
                  >
                    official theming documentation
                  </Link>
                  .
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Base & Utility Styles</List.Title>
                <List.Content>
                  To complement Tailwind's utility classes, some base styles are
                  defined for universal styling, such as font settings and
                  default component padding. These styles use{" "}
                  <Highlight>@apply</Highlight> for streamlined consistency and
                  ensure that components look cohesive when customized with
                  Tailwind.
                </List.Content>
              </List.Item>
            </List>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">
              Tailwind Best Practices for Custom CSS & Helper Utilities
            </div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              For advanced projects, mixing Tailwind CSS with custom CSS can
              optimize code readability and maintainability. Here are some best
              practices for creating custom CSS and helper utilities:
            </Paragraph>
            <List>
              <List.Item>
                <List.Title>Use @apply for Reusable Patterns</List.Title>
                <List.Content>
                  Tailwind’s <Highlight>@apply</Highlight> directive lets you
                  bundle common utility classes into custom CSS classes. This is
                  helpful for creating global styles or modifying third-party
                  components that don’t natively support Tailwind. For example:
                </List.Content>
                <PreviewCode language="css">
                  {`.btn {
  @apply px-4 py-2 bg-primary text-primary-foreground rounded;
}`}
                </PreviewCode>
              </List.Item>
              <List.Item>
                <List.Title>
                  Define Utility Helper Classes for Shared Styles
                </List.Title>
                <List.Content>
                  Use custom CSS sparingly, as excessive custom CSS can reduce
                  the readability Tailwind provides with its utility-first
                  approach. Try to leverage Tailwind's utilities as much as
                  possible to keep your code consistent and easier to debug.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>
                  Use CSS Variables with Tailwind for Theming
                </List.Title>
                <List.Content>
                  When working on themes, using CSS variables in combination
                  with Tailwind is efficient. Define variables in the root and
                  dark themes, and apply them in Tailwind classes to adjust
                  themes dynamically.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Organize Custom CSS</List.Title>
                <List.Content>
                  Keep any custom CSS in well-structured files and directories,
                  such as <Highlight>assets/css</Highlight>, to clearly separate
                  it from Tailwind’s generated CSS. Grouping related styles
                  (e.g., colors, typography) helps make it more maintainable and
                  readable.
                </List.Content>
              </List.Item>
            </List>
            <Paragraph>
              These practices ensure your project remains modular, easy to
              update, and aligns well with Tailwind's principles, while still
              accommodating necessary customizations.
            </Paragraph>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
