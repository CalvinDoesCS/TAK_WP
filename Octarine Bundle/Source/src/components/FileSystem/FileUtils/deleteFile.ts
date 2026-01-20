import { getFile } from "./getFile";
import { type File } from "./types";

const deleteFile = ({
  files,
  path,
  name,
}: {
  files: File;
  path: string;
  name: string;
}) => {
  const errorMessages = [];
  const originFile = getFile({ files, path: path }).result.file;

  if (!originFile || !originFile.entries[name])
    errorMessages.push(
      `The file '${name}' does not exist at the path: '/${path}'`
    );

  if (originFile && originFile.entries[name]) {
    delete originFile.entries[name];
  }

  return {
    status: errorMessages.length ? "failure" : "success",
    errorMessages,
    files,
  };
};

export { deleteFile };
