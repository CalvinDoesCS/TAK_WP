import { useRoutes } from "react-router-dom";
import Default from "../pages";
import Wallpaper from "../pages/Wallpaper";
import Overlay from "../pages/Overlay";
import Notifications from "../pages/Notifications";
import PrivacyAndSecurity from "../pages/PrivacyAndSecurity";
import Account from "../pages/Account";
import KeyboardShortcuts from "../pages/KeyboardShortcuts";
import FileManagement from "../pages/FileManagement";
import DisplaySettings from "../pages/DisplaySettings";
import LanguageAndRegion from "../pages/LanguageAndRegion";
import SoundAndVolume from "../pages/SoundAndVolume";
import Accessibility from "../pages/Accessibility";

const routes = [
  {
    path: "/",
    element: <Default />,
    children: [
      {
        path: "/",
        element: <Wallpaper />,
      },
      {
        path: "/overlay",
        element: <Overlay />,
      },
      {
        path: "/notifications",
        element: <Notifications />,
      },
      {
        path: "/privacy-and-security",
        element: <PrivacyAndSecurity />,
      },
      {
        path: "/account",
        element: <Account />,
      },
      {
        path: "/keyboard-shortcuts",
        element: <KeyboardShortcuts />,
      },
      {
        path: "/file-management",
        element: <FileManagement />,
      },
      {
        path: "/display-settings",
        element: <DisplaySettings />,
      },
      {
        path: "/language-and-region",
        element: <LanguageAndRegion />,
      },
      {
        path: "/sound-and-volume",
        element: <SoundAndVolume />,
      },
      {
        path: "/accessibility",
        element: <Accessibility />,
      },
    ],
  },
  {
    path: "*",
    element: <Default />,
  },
];

function Router() {
  return useRoutes(routes);
}

export default Router;
