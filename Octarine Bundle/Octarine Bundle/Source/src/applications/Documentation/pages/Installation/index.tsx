import List from "../../components/List";
import Link from "../../components/Link";
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
            <div className="text-base font-medium">Prerequisites</div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              To install and run this project, make sure you have the following
              installed on your system:
            </Paragraph>
            <List>
              <List.Item>
                <List.Title>Node.js</List.Title>
                <List.Content>
                  This project requires Node.js to be installed. You can
                  download the latest version{" "}
                  <Link href="https://nodejs.org/en" target="_blank">
                    here
                  </Link>
                  .
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>NPM</List.Title>
                <List.Content>
                  Node Package Manager (NPM) comes bundled with Node.js and is
                  required for managing dependencies.
                </List.Content>
              </List.Item>
            </List>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">Installing Octarine</div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              After downloading the Octarine project files, you’ll find two main
              directories in the package:
            </Paragraph>
            <List>
              <List.Item>
                <List.Title>Source</List.Title>
                <List.Content>
                  This directory contains the main project files where you can
                  begin development. You’ll be able to add new applications and
                  write additional code within this directory.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Documentation</List.Title>
                <List.Content>
                  This directory provides a link to the online documentation,
                  guiding you through Octarine's features, configuration
                  options, and usage.
                </List.Content>
              </List.Item>
            </List>
            <Paragraph>
              To begin, navigate to the root project folder where the{" "}
              <Highlight>package.json</Highlight> file is located. Run the
              following command to install all required packages into the{" "}
              <Highlight>node_modules</Highlight> directory.
            </Paragraph>
            <Paragraph>
              This command will install all dependencies listed in{" "}
              <Highlight>package.json</Highlight>, preparing your environment
              for development. Once complete, you’ll be ready to start working
              with Octarine.
            </Paragraph>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
