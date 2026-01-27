import {
  Layers3,
  Megaphone,
  Volume2,
  SearchCode,
  Timer,
  VenetianMask,
  CloudCog,
  Swords,
  Wallet,
  ShieldHalf,
  ContactRound,
} from "lucide-react";

export const menu = [
  {
    title: "Getting Started",
    menu: [
      {
        title: "Introduction",
        pathname: "/",
        icon: <Layers3 />,
      },
      {
        title: "Installation",
        pathname: "/installation",
        icon: <Megaphone />,
      },
      {
        title: "Tailwind",
        pathname: "/tailwind",
        icon: <Volume2 />,
      },
      {
        title: "Custom CSS",
        pathname: "/custom-css",
        icon: <Swords />,
      },
      {
        title: "Typescript",
        pathname: "/typescript",
        icon: <ShieldHalf />,
      },
      {
        title: "Updating",
        pathname: "/updating",
        icon: <ContactRound />,
      },
    ],
  },
  {
    title: "Development",
    menu: [
      {
        title: "Development Server",
        pathname: "/development-server",
        icon: <Timer />,
      },
      {
        title: "Folder Structure",
        pathname: "/folder-structure",
        icon: <VenetianMask />,
      },
      {
        title: "Routing",
        pathname: "/routing",
        icon: <CloudCog />,
      },
      {
        title: "State Management",
        pathname: "/state-management",
        icon: <SearchCode />,
      },
    ],
  },
  // {
  //   title: "Configuration",
  //   menu: [
  //     {
  //       title: "Theming",
  //       pathname: "/theming",
  //       icon: <Swords />,
  //     },
  //     {
  //       title: "Dark/Light Mode",
  //       pathname: "/dark-or-light-mode",
  //       icon: <Wallet />,
  //     },
  //     {
  //       title: "Wallpaper",
  //       pathname: "/wallpaper",
  //       icon: <ShieldHalf />,
  //     },
  //     {
  //       title: "Overlay",
  //       pathname: "/overlay",
  //       icon: <ContactRound />,
  //     },
  //   ],
  // },
];
