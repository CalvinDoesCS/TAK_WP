import { createContext } from "react";

export interface RightClickMenuContextValue {
  icon?: JSX.Element;
  title: string;
  shortcut?: string;
  onClick?: () => void;
}

export interface RightClickMenuContext {
  rightClickMenu: RightClickMenuContextValue[];
  setRightClickMenu: (value: RightClickMenuContextValue[]) => void;
}

const rightClickMenuContext = createContext<RightClickMenuContext>({
  rightClickMenu: [],
  setRightClickMenu: () => {},
});

export { rightClickMenuContext };
