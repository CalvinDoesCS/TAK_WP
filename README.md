# Octane Desktop - Micro-App Template

A sophisticated Micro-Frontend (MFE) ecosystem that uses WordPress as a host shell and WebAssembly (Wasm) for high-performance application delivery. The UI consists of modular micro-apps connected by a global logic framework.

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                     OCTANE DESKTOP                              │
│                                                                 │
│  ┌────────────────────────────────────────────────────────┐   │
│  │               WORDPRESS HOST SHELL                     │   │
│  │                                                        │   │
│  │   ┌──────────┐  ┌──────────┐  ┌──────────┐          │   │
│  │   │ Micro-App│  │ Micro-App│  │ Micro-App│   ...    │   │
│  │   │  (Wasm)  │  │  (Wasm)  │  │  (Wasm)  │          │   │
│  │   └────┬─────┘  └────┬─────┘  └────┬─────┘          │   │
│  │        │             │             │                  │   │
│  │        └─────────────┼─────────────┘                  │   │
│  │                      │                                │   │
│  │         ┌────────────▼──────────────┐                │   │
│  │         │  Scoped Transition APIs   │                │   │
│  │         │  - JWT Token Exchange     │                │   │
│  │         │  - Scoped Data Access     │                │   │
│  │         │  - App Navigation         │                │   │
│  │         └────────────┬──────────────┘                │   │
│  │                      │                                │   │
│  │         ┌────────────▼──────────────┐                │   │
│  │         │    SSO / Identity         │                │   │
│  │         │  (User Authentication)    │                │   │
│  │         └───────────────────────────┘                │   │
│  └────────────────────────────────────────────────────────┘   │
│                         :8080                                  │
│                                                                 │
│  ┌────────────────────────────────────────────────────────┐   │
│  │         GLOBAL ARCHITECTURAL WIREFRAME                 │   │
│  │     - Navigation/Entry Points                          │   │
│  │     - Views/Data Displays                              │   │
│  │     - Consistent UI/UX Across Apps                     │   │
│  └────────────────────────────────────────────────────────┘   │
│                                                                 │
└─────────────────────────────────────────────────────────────────┘
                            ▲
                            │
                    ┌───────┴────────┐
                    │  User Browser  │
                    │  (Octarine UI) │
                    └────────────────┘
```

## 🎯 Core Concepts

### 1. Host Environment

- **Octane Desktop**: The primary shell for the user interface
- **WordPress**: Acts as the delivery engine, serving pages where apps are embedded
- **Wasm Micro-Apps**: Delivered as embedded WebAssembly apps for near-native performance
- **Language Flexibility**: Write in C++, Rust, C#, or other languages compiled to Wasm

### 2. Connectivity Layer

- **SSO (Single Sign-On)**: Preserves user identity across WordPress shell and Wasm micro-apps
- **Scoped Transition APIs**: Handles data hand-off and navigation between micro-apps
- **Scoped Access**: Each micro-app receives only the data it's authorized to see
- **Global Wireframe**: Standard behavior for navigation and views across all apps

### 3. Backend & Azure Integration

- **Resource Orchestration**: WordPress pages with embedded Wasm apps managed by Azure
- **API Management**: Handles JWT and Scoped API calls
- **Modular Deployment**: Update one micro-app without rebuilding the entire system

## 🚀 Quick Start

### 1. Create the Shared Network (One-time setup)

```bash
cd /home/calvin/Documents/TAK_WP/headless-wordpress
chmod +x scripts/create-network.sh
./scripts/create-network.sh
```

### 2. Start WordPress Host

```bash
cd /home/calvin/Documents/TAK_WP/headless-wordpress
docker-compose up -d
```

### 3. Start the Admin Dashboard (Octarine)

```bash
cd /home/calvin/Documents/TAK_WP/octarine
cp .env.example .env
docker-compose up -d
```

Access at: http://localhost:5173

### 4. Configure CORS for Wasm Apps

```bash
cd /home/calvin/Documents/TAK_WP/headless-wordpress
chmod +x scripts/configure-wasm-cors.sh
./scripts/configure-wasm-cors.sh
```

### 5. Create Your First Micro-App

```bash
# Copy the template
cp -r /home/calvin/Documents/TAK_WP/micro-app-template /home/calvin/Documents/TAK_WP/my-micro-app

cd /home/calvin/Documents/TAK_WP/my-micro-app

# Set up environment
cp .env.example .env

# Install dependencies
npm install

# Start the micro-app
docker-compose up -d
```

## 📁 Project Structure

```
TAK_WP/
├── headless-wordpress/        # WordPress Host Shell
│   ├── docker-compose.yml
│   ├── wordpress/
│   └── scripts/
│       ├── create-network.sh
│       └── configure-wasm-cors.sh
│
├── octarine/                  # Admin Dashboard
│   ├── docker-compose.yml
│   ├── Dockerfile
│   ├── package.json
│   └── src/
│
├── micro-app-template/        # Template for Creating Micro-Apps
│   ├── docker-compose.yml     # Docker services with app-network
│   ├── Dockerfile            # Multi-stage Node.js build
│   ├─The "Hallway Logic" - How Rooms Connect

### Room Transition Flow

```

User in Room A → Clicks Door → Scoped Transition API → Room B
↓ 1. Validate SSO 2. Generate JWT token 3. Scope data access 4. Hand-off to Room B

````

### 1. Entering a Room (Transition Request)

```javascript
// User clicks a "door" in the WordPress UI
fetch("http://localhost:3000/api/transition/dashboard", {
  method: "POST",
  headers: {
    "Content-Type": "application/json",
    "Authorization": "Bearer <sso-token>"
  },
  body: JSON.stringify({
    userId: 123,
    permissions: ["read", "write"],
    from: "home",
    context: { selectedProject: 456 }
  }),
});
````

**Response:**

````json
{
  "success": true,
  "room": "dashboard",
  "token": "eyJhbGciOiJIUzI1NiIs...",
  "scopedData" Model

### SSO (Single Sign-On)

- User authenticates once at the WordPress host
- Identity token persists across all "rooms"
- No re-authentication when moving between micro-apps

### Scoped Transition APIs

Each room transition generates a **scoped token** with:

1. **Time-bound access** (default: 1 hour)
2. **Permission scoping** (read, write, admin)
3. **Data context** (only relevant data for that room)
4. **Audit trail** (tracks room-to-room navigation)

### Example: Scoped Access Flow

```bash
# 1. Request transition to dashboard room
curl -X POST http://localhost:3000/api/transition/dashboard \
  -H "Authorization: Bearer <sso-token>" \
  -H "Content-Type: application/json" \
  -d '{
    "userId": 123,
    "permissions": ["read", "write"],
    "context": {"projectId": 456}
  }'

# Response includes scoped token
{
  "token": "eyJhbGci...",  # Only valid for dashboard + project 456
  "expiresIn": 3600
}

# 2. Use scoped token inside the room
curl http://localhost:3000/api/protected/dashboard/data \
  -H "Authorization: Bearer eyJhbGci-jwt>" }
});
```typescript
// In micro-app backend
const response = await axios.post("http://headless-wp:80/graphql", {
  query: `query { posts { nodes { id title } } }`,
});
````

### Browser → Both

The browser can communicate with both services:

```javascript
// WordPress
fetch('http://localhost:8080/graphql', { ... })

// Micro-App
fetch('http://localhost:3000/api/apps', { ... })
```

## 🔐 Security: Scoped Transition APIs

The template includes JWT-based scoped transitions:

### 1. Request a Room Transition

```bash
curl -X POST http://localhost:3000/api/transition/dashboard \
  -H "Content-Twordpress
WORDPRESS_DB_HOST=wordpress-db:3306
WORDPRESS_DB_NAME=wordpress
```

### Octarine Dashboard (.env)

```bash
VITE_API_URL=http://localhost:8080/wp-json
VITE_APP_NAME=Octarine Admin Dashboard
```

### Micro-App Room (.env)

````bash
NODE_ENV=development
API_PORT=3000
WORDPREThe Host Shell (WordPress)

```bash
cd headless-wordpress
docker-compose up -d

# Access WordPress admin
open http://localhost:8080/wp-admin

# Configure content, install WPGraphQL plugins
# Define ACF fields for room metadata
````

### 2. The Global Control Panel (Octarine Dashboard)

```bash
cd octarine
docker-compose up -d

# Access admin dashboard
open http://localhost:5173

# Manage rooms, configure navigation
# Monitor Wasm app performance
```

### Create a New Room

1. **Copy the template:**

```bash
cp -r micro-app-template my-new-room
cd my-new-room
```

2. **Update the room configuration (`docker-compose.yml`):**

```yaml
services:
  my-new-room-api:
    container_name: octane-my-new-room
    ports:
      - "3001:3000" # Use unique port for each room
    environment:
      - ROOM_NAME=my-new-room
      - ROOM_PERMISSIONS=read,write,admin
```

3. **Define room behavior (`src/index.ts`):**

```typescript
// Configure what data this room can access
const roomConfig = {
  name: "my-new-room",
  allowedDataScopes: ["user-profile", "analytics-data"],
  requiredPermissions: ["read"],
  wasm: {
    entryPoint: "/wasm/my-new-room.wasm",
    memory: "256MB",
  },
};
```

4. **Start the room:**s Room\*\*: http://localhost:3000

- **Dashboard Room**: http://localhost:3001
- **Settings Room**: http://localhost:3002

All communicate over the `app-network` Docker network.
docker exec octane-micro-app-api ping headless-wp

````

### 2. Test GraphQL Proxy

```bash
curl -X POST http://localhost:3000/api/wordpress/graphql \
  -H "Content-Type: application/json" \
  -d '{"query": "{ posts { nodes { id title } } }"}'
````

### 3. Check Health Endpoints

```bash
# WordPress
curl http://localhost:8080/wp-json/

# Micro-App
curl http://localhost:3000/health
```

## 📝 Environment Variables

### WordPress (.env)

```bash
MYSQL_DATABASE=wordpress
MYSQL_USER=wordpress
MYSQL_PASSWORD=your-password
WORDPRESS_DB_HOST=db:3306
```

### Micro-App (.env)

```bash
NODE_ENV=development
API_PORT=3000
WORDPRESS_API_URL=http://headless-wp:80
WORDPRESS_GRAPHQL_URL=http://headless-wp:80/graphql
ALLOWED_ORIGINS=http://localhost:8080,http://headless-wp
JWT_SECRET=change-this-in-production
```

### Phase 1: Foundation

- [x] WordPress host setup (The Building Shell)
- [x] Octarine dashboard (Global Control Panel)
- [x] Docker networking (app-network)
- [ ] SSO integration (Azure AD)
- [ ] Scoped Transition API implementation

### Phase 2: Room Development

- [ ] Compile first Wasm app (Rust/C++/C#)
- [ ] Implement Global Wireframe (Doors & Windows)
- [ ] Create room navigation UI
- [ ] Test scoped data access

### Phase 3: Azure Integration

- [ ] Azure API Management for JWT handling
- [ ] Azure Key Vault for secrets
- [ ] Azure Container Registry for Wasm modules
- [ ] Azure Monitor for room analytics

### Phase 4: Production Deployment

- [ ] Deploy to Azure Container Instances
- [ ] Configure CDN for Wasm delivery
- [ ] Set up CI/CD pipeline
- [ ] Performance monitoring & optimization

## 🏛️ Architecture Principles

1. **Modularity**: Each room is independently deployable
2. **Security**: Scoped access ensures rooms only see authorized data
3. **Performance**: Wasm provides near-native execution speed
4. **Flexibility**: Write rooms in any language (Rust, C++, C#, Go)
5. **Consistency**: Global wireframe ensures uniform UX
6. **Scalability**: Rooms scale independently based on demand

## 🔗 Resources

- [WordPress GraphQL](https://www.wpgraphql.com/)
- [WebAssembly](https://developer.mozilla.org/en-US/docs/WebAssembly)
- [Rust WebAssembly Book](https://rustwasm.github.io/book/)
- [Azure API Management](https://azure.microsoft.com/en-us/services/api-management/)
- [Docker Networks](https://docs.docker.com/network/)
- [JWT Best Practices](https://jwt.io/)

---

**Welcome to Octane Desktop - Your Micro-Frontend Ecosystem** 🚀

_High-performance Wasm micro-apps with intelligent transition APIs._
docker-compose up # Containerized development

````

### 3. Both Running Simultaneously

Both services run independently but communicate over `octane-network`:

- WordPress: http://localhost:8080
- Micro-App: http://localhost:3000

## 🔧 Customization

### Add More Micro-Apps

1. Copy the template:

```bash
cp -r micro-app-template my-new-app
````

2. Update ports in `docker-compose.yml`:

```yaml
ports:
  - "3001:3000" # Change external port
```

3. Update container name:

```yaml
container_name: octane-my-new-app
```

4. Start it:

```bash
docker-compose up -d
```

### Add Database to Micro-App

Add to `docker-compose.yml`:

```yaml
services:
  postgres:
    image: postgres:15-alpine
    environment:
      POSTGRES_DB: microapp
      POSTGRES_USER: user
      POSTGRES_PASSWORD: password
    volumes:
      - postgres_data:/var/lib/postgresql/data
    networks:
      - octane-network

volumes:
  postgres_data:
```

## 🚨 Troubleshooting

### Network Not Found Error

```bash
./scripts/create-network.sh
```

### Can't Connect to WordPress

```bash
# Check if both are on same network
docker network inspect octane-network

# Verify container names
docker ps
```

### CORS Errors

```bash
# Re-run CORS configuration
./scripts/configure-wasm-cors.sh

# Check allowed origins in micro-app .env
```

### Port Already in Use

```bash
# Change ports in docker-compose.yml
ports:
  - "3001:3000"  # Use different external port
```

## 📚 Next Steps

1. **Build Wasm Apps**: Compile your Rust/C++/C# code to Wasm
2. **Serve Wasm Files**: Upload to WordPress or serve from micro-app
3. **Implement SSO**: Add Azure AD or OAuth
4. **Add API Management**: Use Azure APIM for advanced routing
5. **Deploy to Production**: Use Azure Container Instances or AKS

## 🔗 Resources

- [WordPress GraphQL Docs](https://www.wpgraphql.com/)
- [WebAssembly MDN](https://developer.mozilla.org/en-US/docs/WebAssembly)
- [Docker Networks](https://docs.docker.com/network/)
- [JWT.io](https://jwt.io/)

---

**Your Octane Desktop architecture is ready! 🎉**
