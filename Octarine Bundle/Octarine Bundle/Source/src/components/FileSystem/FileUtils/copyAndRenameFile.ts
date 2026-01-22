import { getFile } from "./getFile";
import { type File } from "./types";
import { getNextIndex } from "./getNextIndex";
import { generateUniqueId } from "@/lib/utils";
import { generateUniqueFileName } from "./generateUniqueFileName";

const copyAndRenameFile = ({
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
    const { id, index, ...originEntry } = originFile.entries[name];
    const newFileId = generateUniqueId();
    const newFileName = generateUniqueFileName({
      name,
      entries: destinationFile.entries,
    });
    const newFileIndex = getNextIndex(destinationFile.entries);

    destinationFile.entries[newFileName] = {
      id: newFileId,
      index: newFileIndex,
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

export { copyAndRenameFile };
