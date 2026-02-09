import { RUNTIME_POSTS_AND_MENUS_QUERY } from "../queries/wordpress";
import { MENU_LOCATIONS } from "../constants";
import type { WordPressData } from "../types";

interface FetchWordPressDataParams {
  wpApiBase: string;
}

export async function fetchWordPressData({
  wpApiBase,
}: FetchWordPressDataParams): Promise<{
  data: WordPressData;
  variables: Record<string, string>;
}> {
  const gqlEndpoint = `${wpApiBase}/graphql`;
  const variables = {
    headerLocation: MENU_LOCATIONS.header,
    footerLocation: MENU_LOCATIONS.footer,
  };

  try {
    const res = await fetch(gqlEndpoint, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        query: RUNTIME_POSTS_AND_MENUS_QUERY,
        variables,
      }),
    });

    console.log("GraphQL response status:", res.status, res.statusText);

    if (res.ok) {
      const payload = await res.json();
      if (payload.errors) {
        console.error("❌ GraphQL errors:", payload.errors);
      }

      const data = payload?.data ?? {};
      console.log("✅ Fetched WordPress data:", {
        posts: data.posts?.nodes?.length,
        primaryMenus: data.primaryMenuItems?.nodes?.length,
        footerMenus: data.footerMenuItems?.nodes?.length,
        frontPage: data.page ? "✓" : "✗",
      });

      return { data, variables };
    } else {
      const errorText = await res.text();
      console.error("❌ GraphQL request failed:", res.status, errorText);
      return { data: {}, variables };
    }
  } catch (err) {
    console.error("Failed to fetch WP data:", err);
    return { data: {}, variables };
  }
}
