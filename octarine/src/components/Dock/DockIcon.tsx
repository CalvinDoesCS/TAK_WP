import { MotionValue, motion, useSpring, useTransform } from "framer-motion";
import { useRef } from "react";

interface MainProps
  extends React.DetailedHTMLProps<
    React.ImgHTMLAttributes<HTMLImageElement>,
    HTMLImageElement
  > {
  icon: string;
  mouseX: MotionValue;
}

const imageAssets = import.meta.glob<{
  default: string;
}>("/src/assets/images/icons/*.{jpg,jpeg,png,svg}", { eager: true });

function Main({ icon, mouseX }: MainProps) {
  const magnification = 60;
  const distance = 140;

  const ref = useRef<HTMLImageElement>(null);

  const distanceCalc = useTransform(mouseX, (val: number) => {
    const bounds = ref.current?.getBoundingClientRect() ?? { x: 0, width: 0 };

    return val - bounds.x - bounds.width / 2;
  });

  const widthSync = useTransform(
    distanceCalc,
    [-distance, 0, distance],
    [44, magnification, 44]
  );

  const width = useSpring(widthSync, {
    mass: 0.1,
    stiffness: 150,
    damping: 12,
  });

  return (
    <motion.img
      ref={ref}
      style={{ width }}
      src={imageAssets["/src/assets/images/icons/" + icon].default}
      className="block origin-bottom aspect-square cursor-pointer drop-shadow-[0_4px_3px_rgb(0_0_0_/_30%)]"
    />
  );
}

export default Main;
