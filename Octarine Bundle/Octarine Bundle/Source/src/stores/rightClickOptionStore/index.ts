import { create } from "zustand";

export type Actions = {
  setRightClickOption: (props: { isActive: boolean }) => void;
};

export type RightClickOptionStore = {
  rightClickOption: {
    isActive: boolean;
  };
} & Actions;

export const getRightClickOption =
  (): RightClickOptionStore["rightClickOption"] => {
    const rightClickOption = localStorage.getItem("rightClickOption");

    if (rightClickOption) {
      return JSON.parse(rightClickOption);
    }

    const defaultRightClickOption = {
      isActive: true,
    };

    localStorage.setItem(
      "rightClickOption",
      JSON.stringify(defaultRightClickOption)
    );

    return defaultRightClickOption;
  };

const useRightClickOptionStore = create<RightClickOptionStore>((set) => ({
  rightClickOption: getRightClickOption(),
  setRightClickOption: (properties) => {
    const rightClickOption = localStorage.getItem("rightClickOption");

    if (rightClickOption) {
      localStorage.setItem(
        "rightClickOption",
        JSON.stringify({
          ...JSON.parse(rightClickOption),
          ...properties,
        })
      );
    }

    set((state) => {
      return {
        ...state,
        rightClickOption: {
          ...state.rightClickOption,
          ...properties,
        },
      };
    });
  },
}));

export { useRightClickOptionStore };
