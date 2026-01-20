import { type File } from "./types";

const getNextIndex = (entries: File["entries"]) => {
  return Object.values(entries).reduce((a, b) => Math.max(a, b.index), -1) + 1;
};

export { getNextIndex };
