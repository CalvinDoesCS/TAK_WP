import { create } from "zustand";
import { wallpapers } from "./wallpapers";
import { overlays } from "./overlays";

interface DesktopProperties {
  wallpaper: {
    imageName: string;
    grayscale: boolean;
    backgroundColor: string;
    opacity: number;
    isShow: boolean;
  };
  overlay: {
    gradient: string;
    opacity: number;
    isShow: boolean;
  };
}

export type Actions = {
  setDekstopProperties: (props: {
    wallpaper?: Partial<DesktopProperties["wallpaper"]>;
    overlay?: Partial<DesktopProperties["overlay"]>;
  }) => void;
};

export type DesktopPropertiesStore = {
  desktopProperties: DesktopProperties;
} & Actions;

export const getDesktopProperties =
  (): DesktopPropertiesStore["desktopProperties"] => {
    const desktopProperties = localStorage.getItem("desktopProperties");

    if (desktopProperties) {
      return JSON.parse(desktopProperties);
    }

    const defaultDesktopProperties = {
      wallpaper: {
        imageName: wallpapers[0].images[0].originalImage,
        grayscale: true,
        backgroundColor: "#09090b",
        opacity: 0.3,
        isShow: true,
      },
      overlay: {
        gradient: overlays[0].overlays[0].overlay,
        opacity: 0.2,
        isShow: true,
      },
    };

    localStorage.setItem(
      "desktopProperties",
      JSON.stringify(defaultDesktopProperties)
    );

    return defaultDesktopProperties;
  };

const useDesktopPropertiesStore = create<DesktopPropertiesStore>((set) => ({
  desktopProperties: getDesktopProperties(),
  setDekstopProperties: (properties) => {
    const desktopProperties = localStorage.getItem("desktopProperties");

    if (desktopProperties) {
      localStorage.setItem(
        "desktopProperties",
        JSON.stringify({
          wallpaper: {
            ...JSON.parse(desktopProperties).wallpaper,
            ...properties.wallpaper,
          },
          overlay: {
            ...JSON.parse(desktopProperties).overlay,
            ...properties.overlay,
          },
        })
      );
    }

    set((state) => {
      return {
        desktopProperties: {
          wallpaper: {
            ...state.desktopProperties.wallpaper,
            ...properties.wallpaper,
          },
          overlay: {
            ...state.desktopProperties.overlay,
            ...properties.overlay,
          },
        },
      };
    });
  },
}));

export { useDesktopPropertiesStore, wallpapers, overlays };
