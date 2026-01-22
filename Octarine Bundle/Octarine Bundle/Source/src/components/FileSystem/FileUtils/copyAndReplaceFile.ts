import { getFile } from "./getFile";
import { type File } from "./types";
import { generateUniqueId } from "@/lib/utils";

const copyAndReplaceFile = ({
  files,
  originPath,
  destinationPath,
  name,
}: {
  files: File;
  originPath: string;
  destinationPath: string;
  name: string;
}) => {
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
    const { id: originId, index, ...originEntry } = originFile.entries[name];
    const { id: destinationId, ...destinationEntry } =
      destinationFile.entries[name];
    const newFileId = generateUniqueId();

    destinationFile.entries[name] = {
      id: newFileId,
      ...destinationEntry,
      ...originEntry,
    };
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

  return {
    errorMessages,
  };
};

export { copyAndReplaceFile };
