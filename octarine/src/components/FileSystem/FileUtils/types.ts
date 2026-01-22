interface BaseFile {
  id: string;
  selected: boolean;
  animated: boolean;
  editable: boolean;
  index: number;
  path?: string;
  defaultOpenWithApp?: {
    path: string;
    file: File;
  };
}

interface FileType extends BaseFile {
  type: "file";
  icon: string;
  extension: string;
  component: string;
  entries: Record<string, never>;
}

interface DirectoryType extends BaseFile {
  type: "directory";
  icon: "";
  extension: "";
  component: "";
  entries: {
    [key: string]: File;
  };
}

type File = FileType | DirectoryType;

export type { File, FileType, DirectoryType };
