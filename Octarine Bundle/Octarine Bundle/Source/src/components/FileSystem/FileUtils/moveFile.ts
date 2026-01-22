import { getFile } from "./getFile";
import { type File } from "./types";

interface MoveFile {
  files: File;
  originPath: string;
  destinationPath: string;
  name: string;
}

const moveFile = ({ files, originPath, destinationPath, name }: MoveFile) => {
  const originFile = getFile({ files, path: originPath }).result.file;
  const destinationFile = getFile({ files, path: destinationPath }).result.file;

  const validate = validateInput({
    originFile,
    destinationFile,
    originPath,
    destinationPath,
    name,
  });

  if (!validate.errorMessages.length) {
    destinationFile.entries[name] = {
      ...originFile.entries[name],
    };

    delete originFile.entries[name];
  } else {
    console.table(validate.errorMessages);
  }

  return {
    errorMessages: validate.errorMessages,
    files,
  };
};

interface ValidateInput {
  originFile: File;
  destinationFile: File;
  originPath: string;
  destinationPath: string;
  name: string;
}

const validateInput = ({
  originFile,
  destinationFile,
  originPath,
  destinationPath,
  name,
}: ValidateInput) => {
  const errorMessages = [];

  if (originPath == destinationPath) {
    errorMessages.push({
      code: "SAME_DIRECTORY",
      message: `The file '${name}' cannot be moved to the same directory: '/${originPath}'`,
    });
  }

  if (!originFile || !originFile.entries[name]) {
    errorMessages.push({
      code: "FILE_NOT_FOUND",
      message: `The file '${name}' does not exist at the origin path: '/${originPath}'`,
    });
  }

  if (!destinationFile) {
    errorMessages.push({
      code: "DESTINATION_NOT_FOUND",
      message: `The destination directory does not exist at the path: '/${destinationPath}'`,
    });
  }

  if (destinationFile.entries[name]) {
    errorMessages.push({
      code: "FILE_ALREADY_EXISTS",
      message: `The file already exists at the specified path: '/${destinationPath}/${name}'`,
    });
  }

  return {
    errorMessages,
  };
};

export { moveFile };
