import { getFile } from "./getFile";
import { type File } from "./types";

const createFile = ({
  files,
  path,
  properties,
  name,
}: {
  files: File;
  path: string;
  properties: File;
  name: string;
}) => {
  const errorMessages = [];
  const originFile = getFile({ files, path }).result.file;

  if (!originFile) errorMessages.push(`The path '${path}' does not exist`);

  if (originFile) {
    originFile.entries[name] = properties;
  }

  return {
    status: errorMessages.length ? "failure" : "success",
    errorMessages,
    files,
  };
};

export { createFile };
