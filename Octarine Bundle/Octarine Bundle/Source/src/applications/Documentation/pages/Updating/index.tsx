import List from "../../components/List";
import Paragraph from "../../components/Paragraph";
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
              Updating the Project to the Latest Version
            </div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              Since we don’t provide a public GitHub repository for this
              project, we recommend that users manage updates through their own
              GitHub repositories. This approach allows you to efficiently
              integrate new versions without losing custom modifications. Here’s
              how you can keep your project up-to-date with new releases:
            </Paragraph>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">
              Step-by-Step Update Guide
            </div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              After downloading the Octarine project files, you’ll find two main
              directories in the package:
            </Paragraph>
            <List>
              <List.Item>
                <List.Title>
                  Set Up a Personal Repository (if not done already)
                </List.Title>
                <List.Content>
                  <List>
                    <List.Item>
                      <List.Content>
                        Initialize a new GitHub repository for your project.
                      </List.Content>
                      <List.Content>
                        Push your current project files to this repository,
                        ensuring that your changes are tracked.
                      </List.Content>
                    </List.Item>
                  </List>
                  <Paragraph>
                    <PreviewCode language="plaintext">
                      {`git init
git add .
git commit -m "Initial commit"
git remote add origin <your-github-repo-url>
git push -u origin main`}
                    </PreviewCode>
                  </Paragraph>
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Download the Latest Project Version</List.Title>
                <List.Content>
                  When a new version is available, download the updated files
                  from our distribution source.
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>Integrate New Files</List.Title>
                <List.Content>
                  <List>
                    <List.Item>
                      <List.Content>Extract the downloaded files.</List.Content>
                      <List.Content>
                        Copy and paste the updated files into your local project
                        directory. Be cautious not to overwrite files you’ve
                        modified.
                      </List.Content>
                      <List.Content>
                        Review the changes, especially in configuration files or
                        core components, to ensure compatibility with your
                        customizations.
                      </List.Content>
                    </List.Item>
                  </List>
                </List.Content>
              </List.Item>
              <List.Item>
                <List.Title>
                  Commit and Push Updates to Your Repository
                </List.Title>
                <List.Content>
                  <List>
                    <List.Item>
                      <List.Content>
                        After merging the updates, add and commit the changes:
                      </List.Content>
                      <Paragraph>
                        <PreviewCode language="plaintext">
                          {`git add .
git commit -m "Update to the latest version"`}
                        </PreviewCode>
                      </Paragraph>
                    </List.Item>
                    <List.Item>
                      <List.Content>
                        Push the changes to your GitHub repository:
                      </List.Content>
                      <Paragraph>
                        <PreviewCode language="plaintext">
                          {`git push origin main`}
                        </PreviewCode>
                      </Paragraph>
                    </List.Item>
                  </List>
                </List.Content>
              </List.Item>
            </List>
          </div>
        </div>
        <div className="flex flex-col gap-4">
          <div className="flex items-center">
            <div className="text-base font-medium">
              Tips for Managing Customizations
            </div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              To make future updates smoother, try to keep custom modifications
              modular or documented. This will make it easier to replace only
              the necessary files during each update, reducing the risk of
              overwriting important changes.
            </Paragraph>
            <Paragraph>
              By following these steps, you can efficiently update your project
              while keeping a version-controlled history of all changes.
            </Paragraph>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
