import { getFile } from "./getFile";
import { type File } from "./types";

const updateFileProperties = ({
  files,
  path,
  properties,
  name,
}: {
  files: File;
  path: string;
  properties: Partial<File> & { name?: string };
  name: string;
}) => {
  const errorMessages = [];
  const originFile = getFile({ files, path: path }).result.file;

  if (!originFile || !originFile.entries[name])
    errorMessages.push(
      `The file '${name}' does not exist at the path: '/${path}'`
    );

  if (originFile && originFile.entries[name]) {
    if (originFile.entries[name].type === "directory") {
      originFile.entries[name] = {
        ...originFile.entries[name],
        ...properties,
        type: "directory",
        icon: "",
        extension: "",
        component: "",
      };
    } else {
      originFile.entries[name] = {
        ...originFile.entries[name],
        ...properties,
        type: "file",
        entries: {},
      };
    }

    if (properties.name && properties.name != name) {
      const newEntries: File["entries"] = {};

      Object.entries(originFile.entries).map(([fileName, file]) => {
        if (properties.name && fileName == name) {
          newEntries[properties.name] = originFile.entries[name];
        } else {
          newEntries[fileName] = file;
        }
      });

      originFile.entries = newEntries;
    }
  }

  return {
    status: errorMessages.length ? "failure" : "success",
    errorMessages,
    files,
  };
};

export { updateFileProperties };
