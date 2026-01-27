import { type Actions as FilesStoreActions } from "@/stores/filesStore";
import { type Actions as AppsStoreActions } from "@/stores/appsStore";

const openApp = ({
  path,
  fileName,
  filesStoreActions,
  appsStoreActions,
}: {
  path: string;
  fileName: string;
  filesStoreActions: FilesStoreActions;
  appsStoreActions: AppsStoreActions;
}) => {
  const { selectFile, updateFileProperties } = filesStoreActions;
  const { selectApps, launchApp, bringWindowToFront } = appsStoreActions;

  Object.entries(selectFile(path).entries).map(([fileKey]) => {
    updateFileProperties({
      path: path,
      properties: {
        selected: fileKey == fileName,
      },
      name: fileKey,
    });
  });

  // Open app
  const app = selectApps().find((app) => app.path == path);
  if (!app || (app && !Object.keys(app.windows).length)) {
    launchApp({
      path,
    });
  } else {
    bringWindowToFront({
      path,
      index: Object.keys(app.windows)[0],
    });
  }
};

export { openApp };
