# WordPress + React Integration Setup

## Architecture Overview

This workspace combines multiple technologies into a complete headless WordPress
solution:

### Services Running:

1. **WordPress Backend** (Port 8080) - Headless CMS
2. **NCMAZ Next.js Frontend** (Port 3000) - Production-ready headless WordPress
   theme
3. **MySQL Database** - WordPress database
4. **phpMyAdmin** (Port 8081) - Database management

## Technology Stack

- **WordPress**: Headless CMS providing GraphQL API
- **NCMAZ Faust**: Next.js + React + TailwindCSS + Apollo Client
- **MySQL**: Database
- **Docker**: Container orchestration

## Setup Instructions

### 1. Start the Services

```bash
docker-compose up -d
```

### 2. Install WordPress

1. Visit http://localhost:8080
2. Complete WordPress installation
3. Create admin credentials

### 3. Install NCMAZ Plugin

1. Go to http://localhost:8080/wp-admin
2. Navigate to Plugins
3. The "NCMAZ Faust Core" plugin should be visible (auto-mounted)
4. Activate the plugin
5. Go to Settings → Headless
6. Copy the "Secret Key" generated

### 4. Update NCMAZ Environment

Update the FAUST_SECRET_KEY in:

- `NCMAZ_FAUST/front-end/ncmaz-faust/.env.local`
- `docker-compose.yml` (ncmaz-frontend service)

Replace `your_faust_secret_key_here` with the key from WordPress.

### 5. Restart NCMAZ Frontend

```bash
docker-compose restart ncmaz-frontend
```

## Access Points

- **WordPress Admin**: http://localhost:8080/wp-admin
- **WordPress REST API**: http://localhost:8080/wp-json
- **WordPress GraphQL**: http://localhost:8080/graphql (after plugin activation)
- **NCMAZ Frontend**: http://localhost:3000
- **phpMyAdmin**: http://localhost:8081

## Development Workflow

### For NCMAZ Frontend (Next.js)

The NCMAZ frontend automatically connects to WordPress via GraphQL and displays
your content with a modern, fast interface.

- Add posts/pages in WordPress admin
- They automatically appear on the Next.js frontend
- Customize theme in `NCMAZ_FAUST/front-end/ncmaz-faust/src/`

## Architecture Benefits

1. **Headless CMS**: WordPress as a powerful content management system
2. **Modern Frontend**: Next.js with SSR, SSG, and excellent performance
3. **GraphQL**: Efficient data fetching with NCMAZ Faust plugin
4. **Production Ready**: NCMAZ is a complete, tested theme
5. **SEO Optimized**: Server-side rendering for better search engine visibility

## Next Steps

1. Explore NCMAZ theme customization in `NCMAZ_FAUST/front-end/ncmaz-faust/`
2. Check documentation in `NCMAZ_FAUST/documentation/documentation.html`
3. Add content in WordPress admin
4. Customize the design using TailwindCSS
5. Build for production: `cd NCMAZ_FAUST/front-end/ncmaz-faust && npm run build`

## Troubleshooting

### NCMAZ not connecting to WordPress?

- Verify the FAUST_SECRET_KEY matches WordPress settings
- Check that the plugin is activated
- Ensure WordPress is accessible at http://localhost:8080

### Port conflicts?

- Change ports in docker-compose.yml if needed
- Update NEXT_PUBLIC_URL and NEXT_PUBLIC_WORDPRESS_URL accordingly

### GraphQL not available?

- Make sure NCMAZ Faust Core plugin is activated
- Visit http://localhost:8080/graphql to test
