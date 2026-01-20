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
    title: "Appearance Settings",
    menu: [
      {
        title: "Wallpaper",
        pathname: "/",
        icon: <Layers3 />,
      },
      {
        title: "Overlay",
        pathname: "/overlay",
        icon: <Megaphone />,
      },
      {
        title: "Notifications",
        pathname: "/notifications",
        icon: <Volume2 />,
      },
      {
        title: "Privacy & Security",
        pathname: "/privacy-and-security",
        icon: <SearchCode />,
      },
    ],
  },
  {
    title: "User Preferences",
    menu: [
      {
        title: "Account",
        pathname: "/account",
        icon: <Timer />,
      },
      {
        title: "Keyboard Shortcuts",
        pathname: "/keyboard-shortcuts",
        icon: <VenetianMask />,
      },
      {
        title: "File Management",
        pathname: "/file-management",
        icon: <CloudCog />,
      },
    ],
  },
  {
    title: "System Settings",
    menu: [
      {
        title: "Display Settings",
        pathname: "/display-settings",
        icon: <Swords />,
      },
      {
        title: "Language & Region",
        pathname: "/language-and-region",
        icon: <Wallet />,
      },
      {
        title: "Sound & Volume",
        pathname: "/sound-and-volume",
        icon: <ShieldHalf />,
      },
      {
        title: "Accessibility",
        pathname: "/accessibility",
        icon: <ContactRound />,
      },
    ],
  },
];
