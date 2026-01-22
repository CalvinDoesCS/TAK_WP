# Headless WordPress - Quick Start Guide

## 🎉 Installation Complete!

Your headless WordPress instance is now running and ready to use.

## 📍 Access Points

### WordPress Admin Panel

- **URL**: http://localhost:8080/wp-admin
- **Username**: `admin`
- **Password**: `admin123`

### API Endpoints

- **GraphQL**: http://localhost:8080/graphql
- **REST API**: http://localhost:8080/wp-json
- **WP REST API**: http://localhost:8080/wp-json/wp/v2

## 🔌 Installed Plugins

✅ **WPGraphQL** (v2.6.0) - GraphQL API for WordPress  
✅ **Advanced Custom Fields** (v6.7.0) - Custom field management  
✅ **WPGraphQL for ACF** (v2.4.1) - Expose ACF fields via GraphQL  
✅ **JWT Authentication for WP REST API** - Secure token-based authentication

## 🚀 Test Your Setup

### 1. GraphQL Query Example

Visit http://localhost:8080/graphql and run:

```graphql
query GetAllPosts {
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

### 2. REST API Test

```bash
curl http://localhost:8080/wp-json/wp/v2/posts
```

### 3. Using with Frontend Framework

#### React + Apollo Client

```bash
npm install @apollo/client graphql
```

```javascript
import { ApolloClient, InMemoryCache, gql } from "@apollo/client";

const client = new ApolloClient({
  uri: "http://localhost:8080/graphql",
  cache: new InMemoryCache(),
});

// Fetch posts
const GET_POSTS = gql`
  query GetPosts {
    posts {
      nodes {
        id
        title
        content
      }
    }
  }
`;

const { data } = await client.query({ query: GET_POSTS });
```

#### Next.js Example

```javascript
// app/posts/page.js
async function getPosts() {
  const res = await fetch("http://localhost:8080/graphql", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      query: `
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
    }),
  });

  const { data } = await res.json();
  return data.posts.nodes;
}

export default async function PostsPage() {
  const posts = await getPosts();

  return (
    <div>
      {posts.map((post) => (
        <article key={post.id}>
          <h2>{post.title}</h2>
          <div dangerouslySetInnerHTML={{ __html: post.content }} />
        </article>
      ))}
    </div>
  );
}
```

## 🔐 Authentication

### Get JWT Token (REST API)

```bash
curl -X POST http://localhost:8080/wp-json/jwt-auth/v1/token \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### Use Token in Requests

```bash
curl http://localhost:8080/wp-json/wp/v2/posts \
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

## 🛠️ WP-CLI Commands

```bash
# List all plugins
docker-compose exec wpcli wp plugin list

# Create a new post
docker-compose exec wpcli wp post create \
  --post_title="My New Post" \
  --post_content="Content here" \
  --post_status=publish

# List all posts
docker-compose exec wpcli wp post list

# Create a new user
docker-compose exec wpcli wp user create testuser test@example.com \
  --role=editor --user_pass=password123

# Export database
docker-compose exec wpcli wp db export /var/www/html/backup.sql
```

## 📊 Docker Commands

```bash
# View logs
docker-compose logs -f wordpress

# Stop services
docker-compose down

# Restart services
docker-compose restart

# Start services
docker-compose up -d
```

## 🔧 Customization

### Add Custom Post Types

Use WP-CLI:

```bash
docker-compose exec wpcli wp scaffold post-type movie
```

Or create a custom plugin in `wordpress/wp-content/plugins/`

### Install Additional Plugins

```bash
# Via WP-CLI
docker-compose exec wpcli wp plugin install plugin-name --activate

# Examples:
docker-compose exec wpcli wp plugin install wordpress-seo --activate
docker-compose exec wpcli wp plugin install wp-graphql-yoast-seo --activate
```

## 📁 Project Structure

```
headless-wordpress/
├── docker-compose.yml       # Docker services
├── php.ini                 # PHP configuration
├── setup.sh               # Setup automation
├── README.md              # Main documentation
├── QUICKSTART.md          # This file
└── wordpress/             # WordPress files (auto-created)
    ├── wp-content/
    │   ├── plugins/
    │   ├── themes/
    │   └── uploads/
    └── wp-config.php
```

## 🌐 CORS Configuration

CORS is enabled for all origins by default. For production, edit the WordPress configuration to restrict origins.

## ⚠️ Security Reminders

Before deploying to production:

1. ✅ Change admin password
2. ✅ Update database credentials
3. ✅ Change JWT secret keys
4. ✅ Restrict CORS to specific domains
5. ✅ Enable HTTPS
6. ✅ Use strong passwords
7. ✅ Regular backups
8. ✅ Keep WordPress & plugins updated

## 🆘 Troubleshooting

### WordPress not responding

```bash
docker-compose restart wordpress
docker-compose logs wordpress
```

### Database connection issues

```bash
docker-compose restart db
docker-compose logs db
```

### GraphQL endpoint not working

1. Check if WPGraphQL is activated
2. Flush permalinks: `docker-compose exec wpcli wp rewrite flush`
3. Check WordPress logs

### Reset everything

```bash
docker-compose down -v
rm -rf wordpress
docker-compose up -d
./setup.sh
```

## 📚 Additional Resources

- [WPGraphQL Docs](https://www.wpgraphql.com/docs/introduction)
- [WordPress REST API](https://developer.wordpress.org/rest-api/)
- [WP-CLI Commands](https://developer.wordpress.org/cli/commands/)
- [Docker Compose Docs](https://docs.docker.com/compose/)

## 🎯 Next Steps

1. Create custom post types for your content
2. Add custom fields using ACF
3. Build your frontend application
4. Configure your production environment
5. Set up CI/CD pipeline

---

**Happy coding! 🚀**
