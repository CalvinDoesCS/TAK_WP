import { AuthProvider, AuthToken, AuthUser, AuthConfig } from './types';

/**
 * WordPress JWT Auth Provider
 */
export class WordPressJWTProvider implements AuthProvider {
  private endpoint: string;

  constructor(config: AuthConfig) {
    this.endpoint = config.endpoint; // e.g., http://localhost:8080
  }

  async authenticate(username: string, password: string): Promise<AuthToken> {
    const response = await fetch(`${this.endpoint}/wp-json/jwt-auth/v1/token`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ username, password }),
    });

    if (!response.ok) {
      throw new Error('Authentication failed');
    }

    const data = await response.json();

    return {
      accessToken: data.token,
      expiresIn: 3600, // WordPress JWT default
      tokenType: 'Bearer',
    };
  }

  async validateToken(token: string): Promise<AuthUser> {
    const response = await fetch(`${this.endpoint}/wp-json/wp/v2/users/me`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    if (!response.ok) {
      throw new Error('Token validation failed');
    }

    const data = await response.json();

    return {
      id: data.id.toString(),
      email: data.email,
      name: data.name,
      roles: data.roles || [],
    };
  }

  async refreshToken(refreshToken: string): Promise<AuthToken> {
    // WordPress JWT doesn't support refresh tokens by default
    // Return the same token or implement custom refresh logic
    throw new Error('WordPress JWT does not support token refresh. Use a new login.');
  }

  async logout(token: string): Promise<void> {
    // WordPress JWT logout endpoint (if using extended plugin)
    // Currently just invalidates on client side
    console.log('Token invalidated on client');
  }

  getTokenType(): string {
    return 'Bearer';
  }
}
