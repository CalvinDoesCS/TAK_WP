import type { FrontPageData, WordPressData } from "../types";

export function createFrontPageData(
  runtimeData: WordPressData,
): FrontPageData {
  return {
    __typename: "Page",
    id: "cG9zdDox",
    databaseId: 1,
    title: runtimeData.generalSettings?.title || "Home",
    slug: "home",
    uri: "/",
    isFrontPage: true,
    isContentNode: true,
    isTermNode: false,
    isPostsPage: false,
    template: null,
    contentType: {
      __typename: "ContentType",
      node: {
        name: "page",
      },
    },
    ncPageMeta: {
      isFullWithPage: true,
    },
    editorBlocks: [],
    featuredImage: null,
  };
}
