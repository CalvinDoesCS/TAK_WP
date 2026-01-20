import { createContext } from "react";

export interface FileActionAnimatorInterface {
  element: string;
  duration?: number;
  from: {
    x: number;
    y: number;
  };
  to: {
    x: number;
    y: number;
  };
  onFinish?: () => void;
}

const fileActionAnimatorContext = createContext<{
  fileActionAnimator: FileActionAnimatorInterface;
  setFileActionAnimator: (value: FileActionAnimatorInterface) => void;
}>({
  fileActionAnimator: {
    element: "",
    duration: 0,
    from: {
      x: 0,
      y: 0,
    },
    to: {
      x: 0,
      y: 0,
    },
    onFinish: () => {},
  },
  setFileActionAnimator: () => {},
});

export { fileActionAnimatorContext };
