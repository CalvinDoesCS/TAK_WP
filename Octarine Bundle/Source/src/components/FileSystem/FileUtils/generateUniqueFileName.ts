import { type File } from "./types";

const getOriginalFileName = (name: string) => {
  const splittedNames = name.split(" ");

  if (splittedNames[splittedNames.length - 1] == "copy")
    return splittedNames.slice(0, -1).join(" ");

  if (
    splittedNames[splittedNames.length - 2] == "copy" &&
    !isNaN(parseInt(splittedNames[splittedNames.length - 1]))
  )
    return splittedNames.slice(0, -2).join(" ");

  return splittedNames.join(" ");
};

const generateUniqueFileName = ({
  name,
  entries,
}: {
  name: string;
  entries: File["entries"];
}) => {
  const originalFileName = getOriginalFileName(name);

  // Return original "File Name"
  if (!entries[name]) return name;

  // Generate "File Name copy"
  if (!entries[originalFileName + " copy"]) return originalFileName + " copy";

  // Generate "File Name copy 1, 2, 3++"
  let check = true,
    num = 2;

  while (check) {
    if (!entries[originalFileName + " copy " + num]) {
      check = false;
    }

    num++;
  }

  return originalFileName + " copy " + (num - 1);
};

export { generateUniqueFileName };
