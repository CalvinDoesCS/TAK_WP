import { useRoutes } from "react-router-dom";
import Default from "../pages";
import Inbox from "../pages/Inbox";

const routes = [
  {
    path: "/",
    element: <Default />,
    children: [
      {
        path: "/",
        element: <Inbox />,
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
