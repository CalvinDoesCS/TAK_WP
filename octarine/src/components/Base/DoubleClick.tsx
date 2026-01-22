import React, { useState, MouseEvent } from "react";

export interface MainProps extends React.ComponentPropsWithRef<"div"> {
  onClick?: (e: MouseEvent<HTMLDivElement>) => void;
  onDoubleClick?: (e: MouseEvent<HTMLDivElement>) => void;
}

const Main = React.forwardRef<HTMLDivElement, MainProps>(
  ({ onClick, onDoubleClick, children, ...props }, ref) => {
    const [clickTimeout, setClickTimeout] = useState<NodeJS.Timeout | null>(
      null
    );
    const [lastClickTime, setLastClickTime] = useState<number>(0);

    const handleMouseDown = (e: MouseEvent<HTMLDivElement>) => {
      // Single click
      onClick && onClick(e);

      const currentTime = Date.now();

      if (clickTimeout) {
        clearTimeout(clickTimeout);
        setClickTimeout(null);
      }

      // Double click
      if (currentTime - lastClickTime < 250) {
        onDoubleClick && onDoubleClick(e);
      }

      setLastClickTime(currentTime);
    };

    return (
      <div ref={ref} onMouseDown={handleMouseDown} {...props}>
        {children}
      </div>
    );
  }
);

export default Main;
