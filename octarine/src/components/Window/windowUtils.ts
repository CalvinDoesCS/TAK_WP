const getComputedSize = ({
  desktopEl,
  width,
  height,
}: {
  desktopEl: Element;
  width: string | number;
  height: string | number;
}) => {
  return {
    width:
      typeof width === "string" && width.indexOf("%") !== -1
        ? (parseInt(width) * desktopEl.getBoundingClientRect().width) / 100
        : parseInt(width.toString()) > desktopEl.getBoundingClientRect().width
        ? desktopEl.getBoundingClientRect().width * 0.9
        : parseInt(width.toString()),
    height:
      typeof height === "string" && height.indexOf("%") !== -1
        ? (parseInt(height) * desktopEl.getBoundingClientRect().height) / 100
        : parseInt(height.toString()) > desktopEl.getBoundingClientRect().height
        ? desktopEl.getBoundingClientRect().height * 0.9
        : parseInt(height.toString()),
  };
};

const getComputedPosition = ({
  desktopEl,
  x,
  y,
  width,
  height,
}: {
  desktopEl: Element;
  x: string | number;
  y: string | number;
  width: string | number;
  height: string | number;
}) => {
  const position = {
    x: 0,
    y: 0,
  };
  const computedSize = getComputedSize({
    desktopEl,
    width,
    height,
  });

  // X position
  if (typeof x === "number") position.x = x;
  if (typeof x === "string" && x.indexOf("%") !== -1)
    position.x = (parseInt(x) * desktopEl.getBoundingClientRect().width) / 100;
  if (x == "start") position.x = 0;
  if (x == "center")
    position.x =
      (desktopEl.getBoundingClientRect().width - computedSize.width) / 2;
  if (x == "end")
    position.x = desktopEl.getBoundingClientRect().width - computedSize.width;

  // Y position
  if (typeof y === "number") position.y = y;
  if (typeof y === "string" && y.indexOf("%") !== -1)
    position.y = (parseInt(y) * desktopEl.getBoundingClientRect().height) / 100;
  if (y == "start") position.y = 0;
  if (y == "center")
    position.y =
      (desktopEl.getBoundingClientRect().height - computedSize.height) / 2;
  if (y == "end")
    position.y = desktopEl.getBoundingClientRect().height - computedSize.height;

  return {
    x: position.x,
    y: position.y + desktopEl.getBoundingClientRect().y,
  };
};

export { getComputedSize, getComputedPosition };
