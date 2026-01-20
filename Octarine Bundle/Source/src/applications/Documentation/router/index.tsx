import { useRoutes } from "react-router-dom";
import Default from "../pages";
import Introduction from "../pages/Introduction";
import Installation from "../pages/Installation";
import Tailwind from "../pages/Tailwind";
import CustomCss from "../pages/CustomCss";
import Typescript from "../pages/Typescript";
import Updating from "../pages/Updating";
import DevelopmentServer from "../pages/DevelopmentServer";
import FolderStructure from "../pages/FolderStructure";
import Routing from "../pages/Routing";
import StateManagement from "../pages/StateManagement";

const routes = [
  {
    path: "/",
    element: <Default />,
    children: [
      {
        path: "/",
        element: <Introduction />,
      },
      {
        path: "/installation",
        element: <Installation />,
      },
      {
        path: "/tailwind",
        element: <Tailwind />,
      },
      {
        path: "/custom-css",
        element: <CustomCss />,
      },
      {
        path: "/typescript",
        element: <Typescript />,
      },
      {
        path: "/updating",
        element: <Updating />,
      },
      {
        path: "/development-server",
        element: <DevelopmentServer />,
      },
      {
        path: "/folder-structure",
        element: <FolderStructure />,
      },
      {
        path: "/routing",
        element: <Routing />,
      },
      {
        path: "/state-management",
        element: <StateManagement />,
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
