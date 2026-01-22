import List from "../../components/List";
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
              TypeScript in This Project
            </div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              This project is fully written in TypeScript, ensuring type safety,
              maintainability, and improved development efficiency. TypeScript’s
              static type checking helps identify potential issues early in the
              development process, while also providing rich IDE support for
              features like code completion and refactoring.
            </Paragraph>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">
              TypeScript Configuration (tsconfig.json)
            </div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              The TypeScript configuration for this project is defined in
              <Highlight>tsconfig.json</Highlight> and{" "}
              <Highlight>tsconfig.node.json</Highlight>, each file serving
              different aspects of the codebase. Here’s an overview of the key
              settings and how they contribute to the development experience:
            </Paragraph>
            <List>
              <List.Item>
                <List.Title>Target</List.Title>
                <List.Content>
                  The target JavaScript version is set to{" "}
                  <Highlight>ES2020</Highlight>, ensuring compatibility with
                  modern JavaScript features.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Module System</List.Title>
                <List.Content>
                  The configuration uses <Highlight>module: "ESNext"</Highlight>{" "}
                  and <Highlight>oduleResolution: "bundler"</Highlight>m, which
                  is optimized for bundlers like Vite or Webpack.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Strict Mode</List.Title>
                <List.Content>
                  TypeScript’s strict mode is enabled, enforcing{" "}
                  <Highlight>strict</Highlight> type checks across the project
                  to minimize runtime errors. Additional linting rules like
                  <Highlight>noUnusedLocals</Highlight> and
                  <Highlight>noFallthroughCasesInSwitch</Highlight> provide
                  extra validation.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Type Paths and Module Resolution</List.Title>
                <List.Content>
                  Paths are defined under <Highlight>paths</Highlight> to allow
                  for easier imports, using aliases like{" "}
                  <Highlight>"@/*": ["./src/*"]</Highlight>. The
                  <Highlight>resolveJsonModule</Highlight> and{" "}
                  <Highlight>allowImportingTsExtensions</Highlight> options
                  enhance module flexibility by allowing JSON imports and
                  TypeScript extensions, respectively.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Node-specific Configuration</List.Title>
                <List.Content>
                  The <Highlight>tsconfig.node.json</Highlight> configuration
                  applies to node-specific files (e.g.,{" "}
                  <Highlight>vite.config.ts</Highlight>). It uses{" "}
                  <Highlight>strict</Highlight> mode and allows synthetic
                  default imports, making it compatible with module-based
                  environments.
                </List.Content>
              </List.Item>
            </List>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">
              Adjusting TypeScript Strictness
            </div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              The strict level of TypeScript can be adjusted in the
              <Highlight>tsconfig.json</Highlight> file under the
              <Highlight>compilerOptions</Highlight> section. Here are the
              options:
            </Paragraph>
            <List>
              <List.Item>
                <List.Title>Enable/Disable Strict Mode</List.Title>
                <List.Content>
                  The <Highlight>"strict": true</Highlight> option enables
                  strict mode, enforcing all strict checks. To relax the type
                  checking, set <Highlight>"strict": false</Highlight>.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Granular Strict Settings</List.Title>
                <List.Content>
                  If you want to keep most strict checks but relax a few, you
                  can disable specific options. For example:
                </List.Content>
                <PreviewCode language="json">
                  {`{
  "compilerOptions": {
    "strict": true,
    "strictNullChecks": false, // Disable strict null checks
    "noImplicitAny": false // Allow implicit 'any' types
  }
}`}
                </PreviewCode>
                <List.Content>
                  Disabling individual checks (e.g.,{" "}
                  <Highlight>noImplicitAny</Highlight> or
                  <Highlight>strictNullChecks</Highlight>) provides a balance
                  between type safety and flexibility, useful in scenarios where
                  strict typing is not required across the entire codebase.
                </List.Content>
              </List.Item>
            </List>
            <Paragraph>
              These configurations allow you to fine-tune TypeScript’s
              type-checking behavior to match your development style and the
              complexity of your code.
            </Paragraph>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
