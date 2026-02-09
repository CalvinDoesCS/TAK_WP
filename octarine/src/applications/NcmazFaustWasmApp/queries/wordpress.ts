export const RUNTIME_POSTS_AND_MENUS_QUERY = `
  query RuntimePostsAndMenus($headerLocation: MenuLocationEnum!, $footerLocation: MenuLocationEnum!) {
    generalSettings {
      __typename
      title
      description
    }
    page(id: "/", idType: URI) {
      __typename
      id
      databaseId
      title
      slug
      uri
      isFrontPage
      isContentNode
      isTermNode
      isPostsPage
      contentType {
        __typename
        node {
          name
        }
      }
      editorBlocks {
        __typename
        name
        clientId
        parentClientId
        renderedHtml
      }
      featuredImage {
        node {
          sourceUrl
          altText
          mediaDetails {
            width
            height
          }
        }
      }
    }
    primaryMenuItems: menuItems(where: { location: $headerLocation }, first: 80) {
      nodes {
        id
        target
        uri
        path
        label
        parentId
        cssClasses
        databaseId
        ncmazfaustMenu {
          __typename
          isMegaMenu
          numberOfMenuColumns
          posts {
            nodes {
              ... on Post {
                __typename
                databaseId
                title
                uri
                modified
                date
                excerpt
                categories {
                  nodes {
                    databaseId
                    name
                    slug
                    uri
                  }
                }
                featuredImage {
                  node {
                    sourceUrl
                    altText
                    mediaDetails {
                      width
                      height
                    }
                  }
                }
                postFormats {
                  nodes {
                    name
                    slug
                  }
                }
              }
            }
          }
        }
      }
    }
    footerMenuItems: menuItems(where: { location: $footerLocation }, first: 50) {
      nodes { 
        databaseId
        uri
        label
        target
        parentId
        id
      }
    }
    posts(first: 20) {
      nodes {
        ... on Post {
          __typename
          databaseId
          title
          uri
          status
          modified
          date
          commentStatus
          commentCount
          excerpt
          author {
            node {
              databaseId
              name
              uri
              slug
              avatar {
                url
              }
            }
          }
          categories {
            nodes {
              databaseId
              name
              slug
              uri
            }
          }
          featuredImage {
            node {
              sourceUrl
              altText
              mediaDetails {
                width
                height
              }
            }
          }
          postFormats {
            nodes {
              name
              slug
            }
          }
        }
      }
      pageInfo { 
        hasNextPage 
        endCursor 
        hasPreviousPage 
        startCursor 
      }
    }
  }
`;
