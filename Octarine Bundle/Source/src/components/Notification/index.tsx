import { Toaster } from "@/components/Base/Sonner";
import { toast } from "sonner";
import { useEffect } from "react";
import { cn } from "@/lib/utils";
import _ from "lodash";

function Main() {
  const imageAssets = import.meta.glob<{
    default: string;
  }>("/src/assets/images/icons/**/*.{jpg,jpeg,png,svg}", { eager: true });

  const messages = [
    {
      title: "New Friend Request",
      icon: "adobe-air.svg",
      notification: "Alex sent you a friend request.",
      actionLabel: "Ignore",
    },
    {
      title: "Password Change Successful",
      icon: "applicazione.svg",
      notification: "Your password was successfully changed.",
      actionLabel: "Dismiss",
    },
    {
      title: "Order Shipped",
      icon: "azureus.svg",
      notification:
        "Your order #1234 has been shipped. Track your package for more details.",
      actionLabel: "Track",
    },
    {
      title: "New Comment",
      icon: "application-default-icon.svg",
      notification:
        "Maria commented on your post: 'Great job on your project!'",
      actionLabel: "View",
    },
    {
      title: "System Update Available",
      icon: "brave.svg",
      notification:
        "A new update is available for your device. Click here to install.",
      actionLabel: "Update Now",
    },
    {
      title: "Achievement Unlocked",
      icon: "clipgrab.svg",
      notification: "You reached a new level in the fitness challenge!",
      actionLabel: "Celebrate",
    },
    {
      title: "Payment Received",
      icon: "fingerprint-gui.svg",
      notification: "Your payment of $50 has been received.",
      actionLabel: "View Receipt",
    },
    {
      title: "Weather Alert",
      icon: "flash.svg",
      notification: "Severe thunderstorm warning in your area. Stay safe!",
      actionLabel: "Dismiss",
    },
    {
      title: "Event Reminder",
      icon: "gmail.svg",
      notification: "Don't forget your dentist appointment tomorrow at 10 AM.",
      actionLabel: "Snooze",
    },
    {
      title: "Low Battery",
      icon: "guake.svg",
      notification: "Your device battery is below 20%. Please charge soon.",
      actionLabel: "Dismiss",
    },
    {
      title: "Subscription Renewal",
      icon: "hamster.svg",
      notification:
        "Your subscription will renew tomorrow. Click here to manage.",
      actionLabel: "Manage",
    },
    {
      title: "Friend's Birthday",
      icon: "haguichi.svg",
      notification: "It's Emily's birthday today! Send her a message.",
      actionLabel: "Wish",
    },
    {
      title: "New Message",
      icon: "hyper.svg",
      notification: "You've received a new message from Chris.",
      actionLabel: "Read",
    },
    {
      title: "System Maintenance",
      icon: "insync.svg",
      notification: "Scheduled maintenance will start tonight at midnight.",
      actionLabel: "Acknowledge",
    },
    {
      title: "Sale Alert",
      icon: "kingsoft-presentation.svg",
      notification: "Flash sale on electronics! Don't miss out.",
      actionLabel: "Shop Now",
    },
  ];

  const showNotification = () => {
    const message = _.shuffle(messages)[0];
    toast(message.title, {
      icon: (
        <img
          src={imageAssets["/src/assets/images/icons/" + message.icon].default}
        />
      ),
      description: message.notification,
      action: {
        label: message.actionLabel,
        onClick: () => console.log(message.actionLabel),
      },
    });

    setTimeout(() => {
      showNotification();
    }, _.random(20, 40) * 1000);
  };

  useEffect(() => {
    showNotification();
  }, []);

  return (
    <Toaster
      closeButton
      offset={50}
      position="top-right"
      toastOptions={{
        classNames: {
          toast:
            "[&:hover_[data-button]]:opacity-100 [&:hover_[data-close-button]]:opacity-100 cursor-pointer gap-3.5 group toast group-[.toaster]:bg-background/70 group-[.toaster]:backdrop-blur-sm group-[.toaster]:text-foreground group-[.toaster]:border-border group-[.toaster]:shadow-lg group-[.toaster]:px-5",
          content: "z-[-1]",
          title: "text-foreground/80",
          description: "group-[.toast]:text-foreground/60",
          actionButton: cn([
            "group-[.toast]:!text-foreground/80 group-[.toast]:absolute group-[.toast]:right-0 group-[.toast]:bottom-0 group-[.toast]:!mr-4 group-[.toast]:mb-4 group-[.toast]:!bg-transparent group-[.toast]:!px-3 group-[.toast]:z-20 opacity-0",
            "group-[.toast]:after:content-[''] group-[.toast]:after:absolute group-[.toast]:after:inset-0 group-[.toast]:after:bg-muted/70 group-[.toast]:after:z-[-1] group-[.toast]:after:shadow group-[.toast]:after:rounded-md",
            "group-[.toast]:before:content-[''] group-[.toast]:before:w-[155%] group-[.toast]:before:z-[-1] group-[.toast]:before:-right-1 group-[.toast]:before:blur-lg group-[.toast]:before:-bottom-1 group-[.toast]:before:h-11 group-[.toast]:before:absolute group-[.toast]:before:bg-background/70",
          ]),
          closeButton:
            "group-[.toast]:bg-background/70 group-[.toast]:border-border group-[.toaster]:backdrop-blur-sm group-[.toaster]:opacity-0",
          cancelButton:
            "group-[.toast]:bg-muted group-[.toast]:text-muted-foreground",
          icon: "w-10 h-10 mr-0 drop-shadow-[0_4px_3px_rgb(0_0_0_/_10%)]",
        },
      }}
    />
  );
}

export default Main;
