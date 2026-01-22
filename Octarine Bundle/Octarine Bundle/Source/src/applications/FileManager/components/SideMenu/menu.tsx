import {
  ScreenShare,
  FileBox,
  BookA,
  FileDown,
  Trash2,
  Router,
  Cloudy,
} from "lucide-react";

export const menu = [
  {
    title: "Personalization Settings",
    menu: [
      {
        title: "Desktop",
        pathname: "Desktop",
        icon: <ScreenShare />,
      },
      {
        title: "Documents",
        pathname: "Documents",
        icon: <FileBox />,
      },
      {
        title: "Applications",
        pathname: "System",
        icon: <BookA />,
      },
      {
        title: "Downloads",
        pathname: "Downloads",
        icon: <FileDown />,
      },
      {
        title: "Trash",
        pathname: "Trash",
        icon: <Trash2 />,
      },
    ],
  },
  {
    title: "Locations",
    menu: [
      {
        title: "Network",
        pathname: "Network",
        icon: <Router />,
      },
      {
        title: "Shared",
        pathname: "Shared",
        icon: <Cloudy />,
      },
    ],
  },
  {
    title: "Tags",
    menu: [
      {
        title: "Red",
        pathname: "",
        icon: (
          <div className="bg-red-500 rounded-full w-2.5 h-2.5 mr-2.5"></div>
        ),
      },
      {
        title: "Orange",
        pathname: "",
        icon: (
          <div className="bg-orange-500 rounded-full w-2.5 h-2.5 mr-2.5"></div>
        ),
      },
      {
        title: "Yellow",
        pathname: "",
        icon: (
          <div className="bg-yellow-500 rounded-full w-2.5 h-2.5 mr-2.5"></div>
        ),
      },
      {
        title: "Green",
        pathname: "",
        icon: (
          <div className="bg-green-500 rounded-full w-2.5 h-2.5 mr-2.5"></div>
        ),
      },
      {
        title: "Blue",
        pathname: "",
        icon: (
          <div className="bg-blue-500 rounded-full w-2.5 h-2.5 mr-2.5"></div>
        ),
      },
      {
        title: "Purple",
        pathname: "",
        icon: (
          <div className="bg-purple-500 rounded-full w-2.5 h-2.5 mr-2.5"></div>
        ),
      },
      {
        title: "Gray",
        pathname: "",
        icon: (
          <div className="bg-gray-500 rounded-full w-2.5 h-2.5 mr-2.5"></div>
        ),
      },
    ],
  },
];
