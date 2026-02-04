# Authentication Abstraction Layer (Future Phase)

A pluggable authentication system intended for the **Sync & Auth** phase. It supports both **WordPress JWT** (development) and **Azure Entra ID** (enterprise), allowing provider switching without code changes.

**Status:** Optional in Phase 1. Do not introduce auth coupling unless explicitly required.

## Architecture

```
AuthProvider Interface
├── wordpress-jwt-provider.ts     (Development)
├── entra-id-provider.ts          (Enterprise)
└── AuthFactory                   (Provider instantiation)
    └── AuthConfigLoader          (Configuration management)
        └── useAuth Hook          (React integration)
```

## Quick Start (Phase 2+)

### For Octarine (React/TypeScript)

```typescript
import { useAuth } from '@/services/auth';

export function LoginPage() {
  const { login, isLoading, error } = useAuth();

  const handleLogin = async (username: string, password: string) => {
    try {
      await login(username, password);
      // Redirect to dashboard
    } catch (err) {
      // Show error to user
    }
  };

  return <LoginForm onSubmit={handleLogin} loading={isLoading} error={error} />;
}

export function Dashboard() {
  const { user, logout, isAuthenticated } = useAuth();

  if (!isAuthenticated) {
    return <Redirect to="/login" />;
  }

  return (
    <div>
      <h1>Welcome, {user?.name}</h1>
      <button onClick={logout}>Logout</button>
    </div>
  );
}
```

### For Open Core BS (Laravel)

```php
// In a middleware
use Takwp\Services\Auth\AuthFactory;
use Takwp\Services\Auth\AuthConfigLoader;

class AuthenticateToken {
    public function handle($request, Closure $next) {
        $token = $request->bearerToken();

        if (!$token) {
            return response('Unauthorized', 401);
        }

        try {
            $config = AuthConfigLoader::load();
            $provider = AuthFactory::create($config);
            $user = $provider->validateToken($token);

            $request->merge(['auth_user' => $user]);
            return $next($request);
        } catch (\Exception $e) {
            return response('Unauthorized', 401);
        }
    }
}
```

## Switching Providers

### Development → WordPress JWT

Set environment variable:

```bash
VITE_AUTH_PROVIDER=wordpress-jwt
VITE_AUTH_ENDPOINT=http://localhost:8080
```

**No code changes required.**

### Enterprise → Azure Entra ID

Set environment variables:

```bash
VITE_AUTH_PROVIDER=entra-id
VITE_AUTH_ENDPOINT=https://graph.microsoft.com
VITE_AUTH_CLIENT_ID=your-client-id
VITE_AUTH_CLIENT_SECRET=your-client-secret
VITE_AUTH_TENANT_ID=your-tenant-id
```

**No code changes required.**

## Configuration

### AuthConfig Interface

```typescript
interface AuthConfig {
  provider: "wordpress-jwt" | "entra-id";
  endpoint: string;

  // Entra ID only
  clientId?: string;
  clientSecret?: string;
  tenantId?: string;
}
```

### Environment Variables

| Variable                  | WordPress JWT | Entra ID | Required      |
| ------------------------- | ------------- | -------- | ------------- |
| `VITE_AUTH_PROVIDER`      | ✓             | ✓        | Yes           |
| `VITE_AUTH_ENDPOINT`      | ✓             | ✓        | Yes           |
| `VITE_AUTH_CLIENT_ID`     |               | ✓        | Entra ID only |
| `VITE_AUTH_CLIENT_SECRET` |               | ✓        | Entra ID only |
| `VITE_AUTH_TENANT_ID`     |               | ✓        | Entra ID only |

## Extending with Custom Providers

To add a new auth provider (e.g., Auth0, Okta):

1. **Create a new provider class** implementing `AuthProvider`:

```typescript
import { AuthProvider, AuthToken, AuthUser, AuthConfig } from "./types";

export class Auth0Provider implements AuthProvider {
  async authenticate(username: string, password: string): Promise<AuthToken> {
    // Implementation
  }

  async validateToken(token: string): Promise<AuthUser> {
    // Implementation
  }

  async refreshToken(refreshToken: string): Promise<AuthToken> {
    // Implementation
  }

  async logout(token: string): Promise<void> {
    // Implementation
  }

  getTokenType(): string {
    return "Bearer";
  }
}
```

2. **Update the factory** to support it:

```typescript
case 'auth0':
  return new Auth0Provider(config);
```

3. **Update environment variables**:

```bash
VITE_AUTH_PROVIDER=auth0
```

**No other changes needed.**

## Implementation Details

### WordPress JWT Provider

- **Authentication**: POST to `/wp-json/jwt-auth/v1/token`
- **Token Format**: Standard JWT
- **Validation**: GET `/wp-json/wp/v2/users/me` with Bearer token
- **Refresh**: Not supported by default (stored refresh tokens are newer)
- **Logout**: Client-side only (token remains valid on server)

**Requirements:**

- WordPress with JWT Authentication plugin installed
- JWT secret configured in WordPress

### Entra ID Provider

- **Authentication**: OAuth 2.0 Resource Owner Password flow
- **Token Format**: OAuth 2.0 access/refresh tokens
- **Validation**: Microsoft Graph API `/v1.0/me`
- **Refresh**: Full support via `/oauth2/v2.0/token`
- **Logout**: Implicit (roles managed in Azure)

**Requirements:**

- Azure Entra ID application registration
- Required scopes configured
- User credentials with proper role assignments

## Token Management

### Token Storage

Tokens are persisted in `localStorage` for the duration of the session:

```typescript
// Automatically handled by useAuth hook
localStorage.setItem(
  "authToken",
  JSON.stringify({
    accessToken: "jwt_token_here",
    refreshToken: "refresh_token_here",
    expiresIn: 3600,
    tokenType: "Bearer",
  }),
);
```

### Token Expiration

Monitor token expiration and refresh proactively:

```typescript
const { token, refreshToken } = useAuth();

useEffect(() => {
  if (!token) return;

  const expiresAt = Date.now() + token.expiresIn * 1000;
  const refreshAt = expiresAt - 5 * 60 * 1000; // 5 min before expiry

  const timeout = setTimeout(() => {
    refreshToken();
  }, refreshAt - Date.now());

  return () => clearTimeout(timeout);
}, [token, refreshToken]);
```

## Testing

### Mock Provider for Testing

```typescript
export class MockAuthProvider implements AuthProvider {
  async authenticate(username: string): Promise<AuthToken> {
    return {
      accessToken: "mock-token",
      refreshToken: "mock-refresh",
      expiresIn: 3600,
      tokenType: "Bearer",
    };
  }

  async validateToken(): Promise<AuthUser> {
    return {
      id: "1",
      email: "test@example.com",
      name: "Test User",
      roles: ["admin"],
    };
  }

  async refreshToken(): Promise<AuthToken> {
    return this.authenticate("test");
  }

  async logout(): Promise<void> {}

  getTokenType(): string {
    return "Bearer";
  }
}
```

## Migration Path: WordPress → Entra ID

**Phase 1: Prepare Abstraction** (Current)

- ✅ Implement abstraction layer
- ✅ Support WordPress JWT for development

**Phase 2: Set Up Entra ID** (When ready)

- Register app in Azure Entra ID
- Create `entra-id-provider.ts` (already done)
- Test with staging environment

**Phase 3: Switch to Entra ID** (Gradual rollout)

- Update environment variables
- No code changes required
- Deploy to production

**Phase 4: Deprecate WordPress JWT** (Optional)

- Remove `wordpress-jwt-provider.ts`
- Remove from factory
- Update documentation

## API Integration

### Adding Auth to HTTP Requests

```typescript
// Using fetch
const token = localStorage.getItem("authToken");
const parsed = JSON.parse(token);

fetch("/api/endpoint", {
  headers: {
    Authorization: `Bearer ${parsed.accessToken}`,
  },
});

// Using axios
import axios from "axios";

const api = axios.create();

api.interceptors.request.use((config) => {
  const token = localStorage.getItem("authToken");
  if (token) {
    const parsed = JSON.parse(token);
    config.headers.Authorization = `Bearer ${parsed.accessToken}`;
  }
  return config;
});

// Using within components with useAuth
const { token } = useAuth();
const headers = {
  Authorization: `${token?.tokenType} ${token?.accessToken}`,
};
```

## Troubleshooting

### "Unknown auth provider" Error

Ensure `VITE_AUTH_PROVIDER` environment variable is set to `wordpress-jwt` or `entra-id`.

### "Missing required Entra ID configuration"

When using Entra ID, ensure these are set:

- `VITE_AUTH_CLIENT_ID`
- `VITE_AUTH_TENANT_ID`

### Token validation fails

1. Check token hasn't expired
2. Verify endpoint is correct
3. Ensure provider supports current operation
4. Check network connectivity

### User data missing after login

Ensure provider's `validateToken()` returns complete `AuthUser` object with id, email, name, and roles.

## Files

```
services/auth/
├── types.ts                    (Interface definitions)
├── wordpress-jwt-provider.ts   (WordPress implementation)
├── entra-id-provider.ts        (Azure Entra ID implementation)
├── factory.ts                  (Provider factory)
├── config.ts                   (Configuration loader)
├── useAuth.ts                  (React hook)
├── index.ts                    (Public API)
└── README.md                   (This file)
```

## Performance Considerations

- Token validation happens on app load (once)
- Tokens cached in localStorage (reduces auth calls)
- Consider token refresh strategy to minimize re-authentication
- Provider endpoints should have <100ms latency for best UX

## Security Notes

- **Never store credentials** - only store tokens
- **HTTPS only** in production - tokens are sensitive
- **Refresh tokens** are more sensitive than access tokens
- **Clear tokens** on logout (useAuth hook does this)
- **Validate tokens** on sensitive operations
- **Monitor token expiration** to prevent silent failures

## Next Steps

1. Integrate `useAuth` hook into Octarine components
2. Configure WordPress JWT secret
3. Test login flow with WordPress JWT
4. Add token refresh logic
5. When ready: Register Entra ID app and test switching
