import {
  Inbox,
  File,
  Send,
  ArchiveX,
  Trash,
  Archive,
  UsersRound,
  CircleAlert,
  MessagesSquare,
  ShoppingCart,
} from "lucide-react";

export const menu = [
  {
    title: "All Mails",
    menu: [
      {
        title: "Inbox",
        pathname: "/",
        icon: <Inbox />,
      },
      {
        title: "Drafts",
        pathname: "/",
        icon: <File />,
      },
      {
        title: "Send",
        pathname: "/",
        icon: <Send />,
      },
      {
        title: "Junk",
        pathname: "/",
        icon: <ArchiveX />,
      },
      {
        title: "Trash",
        pathname: "/",
        icon: <Trash />,
      },
      {
        title: "Archive",
        pathname: "/",
        icon: <Archive />,
      },
    ],
  },
  {
    title: "Updates",
    menu: [
      {
        title: "Social",
        pathname: "/",
        icon: <UsersRound />,
      },
      {
        title: "Updates",
        pathname: "/",
        icon: <CircleAlert />,
      },
      {
        title: "Forums",
        pathname: "/",
        icon: <MessagesSquare />,
      },
      {
        title: "Shopping",
        pathname: "/",
        icon: <ShoppingCart />,
      },
      {
        title: "Promotions",
        pathname: "/",
        icon: <Archive />,
      },
    ],
  },
];
