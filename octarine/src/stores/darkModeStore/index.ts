import { create } from "zustand";

export type Actions = {
  setDarkMode: (props: { isActive: boolean }) => void;
};

export type DarkModeStore = {
  darkMode: {
    isActive: boolean;
  };
} & Actions;

export const getDarkModeValue = (): DarkModeStore["darkMode"] => {
  const darkMode = localStorage.getItem("darkMode");

  if (darkMode) {
    return JSON.parse(darkMode);
  }

  const defaultDarkModeValue = {
    isActive: true,
  };

  localStorage.setItem("darkMode", JSON.stringify(defaultDarkModeValue));

  return defaultDarkModeValue;
};

const useDarkModeStore = create<DarkModeStore>((set) => ({
  darkMode: getDarkModeValue(),
  setDarkMode: (properties) => {
    const darkMode = localStorage.getItem("darkMode");

    if (darkMode) {
      localStorage.setItem(
        "darkMode",
        JSON.stringify({
          ...JSON.parse(darkMode),
          ...properties,
        })
      );
    }

    set((state) => {
      return {
        ...state,
        darkMode: {
          ...state.darkMode,
          ...properties,
        },
      };
    });
  },
}));

export { useDarkModeStore };
