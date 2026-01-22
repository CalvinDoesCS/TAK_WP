import { create } from "zustand";
import { getFile } from "@/components/FileSystem/FileUtils/getFile";
import { updateFileProperties as updateProperties } from "@/components/FileSystem/FileUtils/updateFileProperties";
import { moveFile as utilsMoveFile } from "@/components/FileSystem/FileUtils/moveFile";
import { moveAndReplaceFile as utilsMoveAndReplaceFile } from "@/components/FileSystem/FileUtils/moveAndReplaceFile";
import { moveAndRenameFile as utilsMoveAndRenameFile } from "@/components/FileSystem/FileUtils/moveAndRenameFile";
import { copyFile as utilsCopyFile } from "@/components/FileSystem/FileUtils/copyFile";
import { copyAndReplaceFile as utilsCopyAndReplaceFile } from "@/components/FileSystem/FileUtils/copyAndReplaceFile";
import { copyAndRenameFile as utilsCopyAndRenameFile } from "@/components/FileSystem/FileUtils/copyAndRenameFile";
import { deleteFile as utilsDeleteFile } from "@/components/FileSystem/FileUtils/deleteFile";
import { type File } from "@/components/FileSystem/FileUtils/types";
import { useAppsStore } from "../appsStore";
import { type Files, files } from "./files";

export type Actions = {
  selectFile: (path?: string) => File;
  moveFile: (props: {
    originPath: string;
    destinationPath: string;
    name: string;
  }) => ReturnType<typeof utilsMoveFile>;
  moveAndReplaceFile: (props: {
    originPath: string;
    destinationPath: string;
    name: string;
  }) => ReturnType<typeof utilsMoveAndReplaceFile>;
  moveAndRenameFile: (props: {
    originPath: string;
    destinationPath: string;
    name: string;
  }) => ReturnType<typeof utilsMoveAndRenameFile>;
  copyFile: (props: {
    originPath: string;
    destinationPath: string;
    name: string;
  }) => ReturnType<typeof utilsCopyFile>;
  copyAndReplaceFile: (props: {
    originPath: string;
    destinationPath: string;
    name: string;
  }) => ReturnType<typeof utilsCopyAndReplaceFile>;
  copyAndRenameFile: (props: {
    originPath: string;
    destinationPath: string;
    name: string;
  }) => ReturnType<typeof utilsCopyAndRenameFile>;
  deleteFile: (props: { path: string; name: string }) => void;
  updateFileProperties: (props: {
    properties: Partial<File> & { name?: string };
    path: string;
    name: string;
  }) => void;
};

export type FileStore = Files & Actions;

const useFilesStore = create<FileStore>((set, get) => ({
  ...files,
  selectFile: (path = "") => {
    const files = getFile({
      files: get().Root,
      path: path,
    }).result.file;

    if (files) {
      Object.entries(files.entries).map(([fileKey, file]) => {
        // Add full path
        files.entries[fileKey].path = path;

        // Find default supported app
        let newPath = "";

        const defaultOpenWithApp = Object.entries(
          useAppsStore.getState().apps
        ).filter(([appKey, app]) => {
          return app.supportedFileExtensions.find(
            (extension) => extension === file.extension
          );
        })[0];

        if (defaultOpenWithApp) {
          newPath = defaultOpenWithApp[0];
        }

        if (file.type === "directory") {
          newPath = "System/File Manager";
        }

        if (path.length) {
          files.entries[fileKey].defaultOpenWithApp = {
            path: newPath,
            file: getFile({
              files: get().Root,
              path: newPath,
            }).result.file,
          };
        }
      });
    }

    return files;
  },
  moveFile: ({ originPath, destinationPath, name }) => {
    const moveFileResult = utilsMoveFile({
      files: get().Root,
      originPath,
      destinationPath,
      name,
    });

    set((state) => {
      return {
        ...state,
        value: moveFileResult,
      };
    });

    return moveFileResult;
  },
  moveAndReplaceFile: ({ originPath, destinationPath, name }) => {
    const moveAndReplaceFileResult = utilsMoveAndReplaceFile({
      files: get().Root,
      originPath,
      destinationPath,
      name,
    });

    set((state) => {
      return {
        ...state,
        value: moveAndReplaceFileResult,
      };
    });

    return moveAndReplaceFileResult;
  },
  moveAndRenameFile: ({ originPath, destinationPath, name }) => {
    const moveAndRenameFileResult = utilsMoveAndRenameFile({
      files: get().Root,
      originPath,
      destinationPath,
      name,
    });

    set((state) => {
      return {
        ...state,
        value: moveAndRenameFileResult,
      };
    });

    return moveAndRenameFileResult;
  },
  copyFile: ({ originPath, destinationPath, name }) => {
    const copyFileResult = utilsCopyFile({
      files: get().Root,
      originPath,
      destinationPath,
      name,
    });

    set((state) => {
      return {
        ...state,
        value: copyFileResult,
      };
    });

    return copyFileResult;
  },
  copyAndReplaceFile: ({ originPath, destinationPath, name }) => {
    const copyAndReplaceFileResult = utilsCopyAndReplaceFile({
      files: get().Root,
      originPath,
      destinationPath,
      name,
    });

    set((state) => {
      return {
        ...state,
        value: copyAndReplaceFileResult,
      };
    });

    return copyAndReplaceFileResult;
  },
  copyAndRenameFile: ({ originPath, destinationPath, name }) => {
    const copyAndRenameFileResult = utilsCopyAndRenameFile({
      files: get().Root,
      originPath,
      destinationPath,
      name,
    });

    set((state) => {
      return {
        ...state,
        value: copyAndRenameFileResult,
      };
    });

    return copyAndRenameFileResult;
  },
  deleteFile: ({ path, name }) =>
    set((state) => {
      return {
        ...state,
        value: utilsDeleteFile({
          files: state.Root,
          path,
          name,
        }),
      };
    }),
  updateFileProperties: ({ path, properties, name }) =>
    set((state) => {
      return {
        ...state,
        value: updateProperties({
          files: state.Root,
          path,
          properties,
          name,
        }),
      };
    }),
}));

export { useFilesStore };
