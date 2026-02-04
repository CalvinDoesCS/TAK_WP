# Headless WordPress (Host & Provisioning)

WordPress acts as the **host and provisioning layer** for WASM micro-apps. It serves WASM files, injects session/config metadata at load time, and owns user identity and provisioning metadata in future phases.

**Important:** WordPress is **not** the runtime database for WASM application state.

## Features

- **WordPress** latest version
- **MySQL 8.0** database
- **WPGraphQL** (optional) for CMS data access
- **JWT Authentication** (future phase)
- **Advanced Custom Fields** for content modeling
- **WP-CLI** for administration
- **CORS enabled** for WASM module delivery

## Quick Start

### Prerequisites

- Docker
- Docker Compose

### Installation

1. **Start the services:**

   ```bash
   docker-compose up -d
   ```

2. **Run the setup script:**

   ```bash
   chmod +x scripts/setup.sh
   ./scripts/setup.sh
   ```

3. **Configure CORS (after WordPress is installed):**
   ```bash
   chmod +x scripts/configure-cors.sh
   ./scripts/configure-cors.sh
   ```

### Access Points

- **WordPress Admin**: http://localhost:8080/wp-admin
  - Username: `admin`
  - Password: `admin123`
- **GraphQL Endpoint** (optional): http://localhost:8080/graphql
- **REST API**: http://localhost:8080/wp-json
- **WASM Endpoint**: http://localhost:8080/wp-json/tak/v1/wasm/{module}

## GraphQL Queries

### Example: Fetch Posts

```graphql
query GetPosts {
  posts {
    nodes {
      id
      title
      content
      date
      author {
        node {
          name
        }
      }
    }
  }
}
```

### Example: Fetch Pages

```graphql
query GetPages {
  pages {
    nodes {
      id
      title
      content
    }
  }
}
```

## JWT Authentication (Future Phase)

### Get Auth Token

```graphql
mutation LoginUser {
  login(input: { username: "admin", password: "admin123" }) {
    authToken
    user {
      id
      name
    }
  }
}
```

Use the returned `authToken` in your requests:

```
Authorization: Bearer YOUR_AUTH_TOKEN
```

## WP-CLI Usage

Execute WP-CLI commands:

```bash
docker-compose exec wpcli wp --info
docker-compose exec wpcli wp plugin list
docker-compose exec wpcli wp theme list
docker-compose exec wpcli wp user list
```

## Common Commands

### Stop services:

```bash
docker-compose down
```

### Stop and remove volumes (⚠️ deletes database):

```bash
docker-compose down -v
```

### View logs:

```bash
docker-compose logs -f wordpress
```

### Restart services:

```bash
docker-compose restart
```

### Backup database:

```bash
docker-compose exec wpcli wp db export /var/www/html/backup.sql
```

### Import database:

```bash
docker-compose exec wpcli wp db import /var/www/html/backup.sql
```

## Frontend Integration

This WordPress host can be used with any frontend framework for **CMS data**. WASM micro-apps should remain autonomous and only rely on WordPress for provisioning or optional metadata.

- **React/Next.js** - Use Apollo Client or urql
- **Vue/Nuxt** - Use Apollo Client or Vue Apollo
- **Svelte/SvelteKit** - Use GraphQL clients
- **Gatsby** - Use gatsby-source-wordpress

### Example with Apollo Client (React)

```javascript
import { ApolloClient, InMemoryCache, gql } from "@apollo/client";

const client = new ApolloClient({
  uri: "http://localhost:8080/graphql",
  cache: new InMemoryCache(),
});

// Fetch posts
const { data } = await client.query({
  query: gql`
    query GetPosts {
      posts {
        nodes {
          id
          title
          content
        }
      }
    }
  `,
});
```

## Security Notes

⚠️ **For Production:**

1. Change default passwords in `.env.example`
2. Update `GRAPHQL_JWT_AUTH_SECRET_KEY` with a strong secret
3. Configure proper CORS origins (not `*`)
4. Use HTTPS
5. Enable WordPress security plugins
6. Regular backups
7. Update WordPress and plugins regularly

## Plugins Included

- **WPGraphQL** - GraphQL API
- **WPGraphQL JWT Authentication** - Token-based auth
- **Advanced Custom Fields** - Custom fields
- **WPGraphQL for ACF** - Expose ACF fields via GraphQL

## Additional Plugin Recommendations

Install via WP-CLI:

```bash
# Yoast SEO
docker-compose exec wpcli wp plugin install wordpress-seo --activate

# WPGraphQL for Yoast SEO
docker-compose exec wpcli wp plugin install wp-graphql-yoast-seo --activate

# WP REST API Cache
docker-compose exec wpcli wp plugin install wp-rest-cache --activate
```

## Troubleshooting

### WordPress not loading:

```bash
docker-compose logs wordpress
```

### Database connection issues:

```bash
docker-compose logs db
```

### Reset everything:

```bash
docker-compose down -v
rm -rf wordpress
docker-compose up -d
./setup.sh
```

## File Structure

```
headless-wordpress/
├── docker-compose.yml      # Docker services configuration
├── .gitignore              # Git ignore rules
├── README.md               # This file
├── config/                 # Configuration files
│   ├── php.ini            # PHP configuration
│   ├── .env.example       # Environment variables template
│   └── README.md          # Config documentation
├── scripts/                # Utility scripts
│   ├── setup.sh           # Main setup script
│   ├── setup-jwt.sh       # JWT authentication setup
│   ├── configure-cors.sh  # CORS configuration
│   ├── test-setup.sh      # Testing script
│   └── README.md          # Scripts documentation
├── docs/                   # Documentation
│   └── QUICKSTART.md      # Quick start guide
├── examples/               # Integration examples
│   ├── demo.html          # HTML demo page
│   └── README.md          # Examples documentation
└── wordpress/              # WordPress files (auto-created)
    ├── wp-content/
    │   ├── plugins/
    │   ├── themes/
    │   └── uploads/
    └── wp-config.php
```

## Support & Resources

- [WPGraphQL Documentation](https://www.wpgraphql.com/docs/introduction)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [Docker Documentation](https://docs.docker.com/)
- [WP-CLI Commands](https://developer.wordpress.org/cli/commands/)

## License

This setup is provided as-is for development purposes.
