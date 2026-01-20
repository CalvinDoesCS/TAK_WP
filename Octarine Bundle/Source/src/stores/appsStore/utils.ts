import { App } from "./index";

const getHighestZIndex = (value: { [key: string]: App }) => {
  return (
    Math.max(
      ...Object.entries(value).map(([appKey, app]) => {
        return Object.keys(app.windows).length
          ? Math.max(
              ...Object.entries(app.windows).map(
                ([windowId, window]) => window.zIndex
              )
            )
          : 0;
      })
    ) + 1
  );
};

export { getHighestZIndex };
