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
              Project Directory Structure
            </div>
          </div>
          <div className="flex flex-col gap-3">
            <Paragraph>
              This section provides a quick overview of the main directories and
              files in the project structure. Each directory and file plays a
              specific role in the project organization and functionality.
            </Paragraph>
            <PreviewCode language="plaintext">
              {`├── public                        # Contains static files such as icons, images, and HTML files that are publicly accessible.
├── src                           # The main source directory where all the application code resides.
│   ├── applications              # Contains the main application modules.
│   │   ├── ...                   # Additional application-specific files.
│   ├── assets                    # Stores static assets like styles and images.
│   │   ├── css                   # Custom CSS files for global styling.
│   │   └── images                # Images used across the project.
│   ├── components                # Contains reusable UI components organized by functionality.
│   │   ├── ApplicationLoader     # Handles the application loading state.
│   │   ├── Base                  # Base-level components shared across the application.
│   │   ├── Desktop               # Components related to the desktop view and layout.
│   │   ├── Dock                  # Contains the Dock component, typically for quick access to apps.
│   │   ├── FileSystem            # Components related to file operations and structure.
│   │   ├── LockScreen            # Lock screen components for application security.
│   │   ├── MenuBar               # Contains components for the top menu bar.
│   │   ├── MobileMenu            # Menu components specifically for mobile views.
│   │   ├── Notification          # Handles notifications display and settings.
│   │   ├── RightClickMenu        # Context menu components for right-click interactions.
│   │   ├── Wallpaper             # Manages the desktop wallpaper settings.
│   │   └── Window                # Components for window management within the application.
│   ├── hooks                     # Custom React hooks used across the project.
│   │   └── ...                   # Additional hooks.
│   ├── lib                       # Utility libraries or helper functions.
│   │   └── ...                   # Additional libraries.
│   ├── stores                    # Centralized state management files.
│   │   └── ...                   # Additional store-related files.
│   |   App.tsx                   # The root component for the application.
│   |   main.tsx                  # Entry point for the React application, rendering \`App.tsx\`.
│   └── vite-env.d.ts             # Type definitions for Vite environment variables.
├── .eslintrc.cjs                 # ESLint configuration file for code linting rules.
├── .gitignore                    # Specifies files and directories to be ignored by Git.
├── components.json               # JSON file to define and register UI components.
├── index.html                    # The main HTML file used by Vite to load the app.
├── package.json                  # Manages project dependencies, scripts, and metadata.
├── postcss.config.cjs            # Configuration for PostCSS, used in conjunction with Tailwind CSS.
├── README.md                     # Documentation file describing the project, setup, and usage.
├── tailwind.config.js            # Configuration file for Tailwind CSS.
├── tsconfig.json                 # TypeScript configuration file for setting compiler options.
├── tsconfig.node.json            # Additional TypeScript config for Vite or node-specific settings.
└── vite.config.ts                # Configuration file for Vite bundler, handling dev and build settings.`}
            </PreviewCode>
            <Paragraph>
              This structure helps maintain a well-organized and modular
              codebase, making it easier to locate and manage various aspects of
              the application.
            </Paragraph>
          </div>
        </div>
      </div>
    </div>
  );
}

export default Main;
