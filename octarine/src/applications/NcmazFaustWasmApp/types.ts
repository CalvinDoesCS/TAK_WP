export interface FileRecord {
  path: string;
  bytes: Uint8Array;
  mime: string | null;
  url: string;
}

export interface WordPressData {
  page?: FrontPageData;
  generalSettings?: {
    __typename: string;
    title: string;
    description: string;
  };
  primaryMenuItems?: {
    nodes: any[];
  };
  footerMenuItems?: {
    nodes: any[];
  };
  posts?: {
    nodes: any[];
    pageInfo: any;
  };
}

export interface FrontPageData {
  __typename: string;
  id: string;
  databaseId: number;
  title: string;
  slug: string;
  uri: string;
  isFrontPage: boolean;
  isContentNode: boolean;
  isTermNode: boolean;
  isPostsPage: boolean;
  template: null;
  contentType: {
    __typename: string;
    node: {
      name: string;
    };
  };
  ncPageMeta: {
    isFullWithPage: boolean;
  };
  editorBlocks: any[];
  featuredImage: null;
}

export interface AssetMap {
  [key: string]: string;
}
