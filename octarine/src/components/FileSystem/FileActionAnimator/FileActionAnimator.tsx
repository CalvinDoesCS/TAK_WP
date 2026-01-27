import { useState, useEffect, useRef } from "react";
import parse from "html-react-parser";
import clsx from "clsx";
import { useSpring, animated } from "@react-spring/web";
import {
  fileActionAnimatorContext,
  type FileActionAnimatorInterface,
} from "./fileActionAnimatorContext";

export interface MainProps extends React.PropsWithChildren {}

function Main({ children }: MainProps) {
  const [fileActionAnimator, setFileActionAnimator] =
    useState<FileActionAnimatorInterface>({
      element: "",
      duration: 200,
      from: {
        x: 0,
        y: 0,
      },
      to: {
        x: 0,
        y: 0,
      },
      onFinish: () => {
        setFileActionAnimator({
          ...fileActionAnimator,
          element: "",
        });
      },
    });
  const [isAnimating, setIsAnimating] = useState(false);
  const prevCoordinates = useRef({
    from: { x: fileActionAnimator.from.x, y: fileActionAnimator.from.y },
    to: { x: fileActionAnimator.to.x, y: fileActionAnimator.to.y },
  });

  const [styles, api] = useSpring(() => ({
    from: {
      x: 0,
      y: 0,
    },
    to: {
      x: 0,
      y: 0,
    },
    config: {
      duration: fileActionAnimator.duration,
    },
  }));

  useEffect(() => {
    if (
      fileActionAnimator.element.length &&
      (prevCoordinates.current.from.x !== fileActionAnimator.from.x ||
        prevCoordinates.current.from.y !== fileActionAnimator.from.y ||
        prevCoordinates.current.to.x !== fileActionAnimator.to.x ||
        prevCoordinates.current.to.y !== fileActionAnimator.to.y)
    ) {
      setIsAnimating(true);
      api.start({
        from: {
          x: fileActionAnimator.from.x,
          y: fileActionAnimator.from.y,
        },
        to: {
          x: fileActionAnimator.to.x,
          y: fileActionAnimator.to.y,
        },
      });

      // Update the previous coordinates
      prevCoordinates.current = {
        from: { x: fileActionAnimator.from.x, y: fileActionAnimator.from.y },
        to: { x: fileActionAnimator.to.x, y: fileActionAnimator.to.y },
      };

      setTimeout(() => {
        setIsAnimating(false);

        if (fileActionAnimator.onFinish) {
          fileActionAnimator.onFinish();
        }
      }, fileActionAnimator.duration);
    }
  }, [fileActionAnimator, api]);

  return (
    <fileActionAnimatorContext.Provider
      value={{
        fileActionAnimator,
        setFileActionAnimator,
      }}
    >
      {isAnimating && (
        <animated.div
          style={styles}
          className={clsx(["fixed", { "z-[999999]": isAnimating }])}
        >
          <div className="relative flex items-center justify-center w-full h-full">
            {parse(fileActionAnimator.element)}
          </div>
        </animated.div>
      )}
      {children}
    </fileActionAnimatorContext.Provider>
  );
}

export default Main;
