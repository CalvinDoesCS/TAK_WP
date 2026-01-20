import { type File } from "./types";

const getFile = ({ files, path }: { files: File; path: string }) => {
  const errorMessages = [];
  const paths = path.split("/");
  const file =
    path == "/"
      ? files
      : paths.reduce((current, part) => current.entries[part] ?? null, files);

  if (!file) errorMessages.push(`The file '/${path}' does not exist`);

  return {
    status: !file ? "failure" : "success",
    errorMessages,
    result: {
      file,
      fileName: paths[paths.length - 1],
    },
  };
};

export { getFile };
