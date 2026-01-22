import { useState, useEffect } from "react";
import { ctrlKeyContext } from "./ctrlKeyContext";

export interface MainProps extends React.PropsWithChildren {}

function Main({ children }: MainProps) {
  const [isCtrlKeyPressed, setIsCtrlKeyPressed] = useState<boolean>(false);

  useEffect(() => {
    const handleKeyDown = (event: globalThis.KeyboardEvent) => {
      if (event.ctrlKey || event.metaKey) {
        setIsCtrlKeyPressed(true);
      }
    };

    const handleKeyUp = (event: globalThis.KeyboardEvent) => {
      if (!event.ctrlKey && !event.metaKey) {
        setIsCtrlKeyPressed(false);
      }
    };

    const handleWindowBlur = () => {
      setIsCtrlKeyPressed(false);
    };

    window.addEventListener("keydown", handleKeyDown);
    window.addEventListener("keyup", handleKeyUp);
    window.addEventListener("blur", handleWindowBlur);

    return () => {
      window.removeEventListener("keydown", handleKeyDown);
      window.removeEventListener("keyup", handleKeyUp);
      window.removeEventListener("blur", handleWindowBlur);
    };
  }, []);

  return (
    <ctrlKeyContext.Provider value={{ isCtrlKeyPressed }}>
      {children}
    </ctrlKeyContext.Provider>
  );
}

export default Main;
