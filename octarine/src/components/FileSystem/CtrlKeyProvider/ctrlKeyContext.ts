import { createContext } from "react";

export interface ctrlKeyContextInterface {
  isCtrlKeyPressed: boolean;
}

const ctrlKeyContext = createContext<ctrlKeyContextInterface>({
  isCtrlKeyPressed: false,
});

export { ctrlKeyContext };
