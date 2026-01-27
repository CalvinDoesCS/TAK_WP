import { AuthProvider, AuthToken, AuthUser, AuthConfig } from './types';

/**
 * Azure Entra ID Auth Provider
 * Implements OAuth 2.0 / OIDC
 */
export class EntraIDProvider implements AuthProvider {
  private endpoint: string;
  private clientId: string;
  private clientSecret: string;
  private tenantId: string;

  constructor(config: AuthConfig) {
    this.endpoint = config.endpoint; // e.g., https://graph.microsoft.com
    this.clientId = config.clientId || '';
    this.clientSecret = config.clientSecret || '';
    this.tenantId = config.tenantId || '';
  }

  async authenticate(username: string, password: string): Promise<AuthToken> {
    const tokenUrl = `https://login.microsoftonline.com/${this.tenantId}/oauth2/v2.0/token`;

    const response = await fetch(tokenUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        client_id: this.clientId,
        client_secret: this.clientSecret,
        username,
        password,
        grant_type: 'password',
        scope: 'https://graph.microsoft.com/.default',
      }).toString(),
    });

    if (!response.ok) {
      throw new Error('Entra ID authentication failed');
    }

    const data = await response.json();

    return {
      accessToken: data.access_token,
      refreshToken: data.refresh_token,
      expiresIn: data.expires_in,
      tokenType: 'Bearer',
    };
  }

  async validateToken(token: string): Promise<AuthUser> {
    const response = await fetch(`${this.endpoint}/v1.0/me`, {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    });

    if (!response.ok) {
      throw new Error('Token validation failed');
    }

    const data = await response.json();

    return {
      id: data.id,
      email: data.mail || data.userPrincipalName,
      name: data.displayName,
      roles: data.jobTitle ? [data.jobTitle] : [],
    };
  }

  async refreshToken(refreshToken: string): Promise<AuthToken> {
    const tokenUrl = `https://login.microsoftonline.com/${this.tenantId}/oauth2/v2.0/token`;

    const response = await fetch(tokenUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        client_id: this.clientId,
        client_secret: this.clientSecret,
        refresh_token: refreshToken,
        grant_type: 'refresh_token',
        scope: 'https://graph.microsoft.com/.default',
      }).toString(),
    });

    if (!response.ok) {
      throw new Error('Token refresh failed');
    }

    const data = await response.json();

    return {
      accessToken: data.access_token,
      refreshToken: data.refresh_token,
      expiresIn: data.expires_in,
      tokenType: 'Bearer',
    };
  }

  async logout(token: string): Promise<void> {
    // Entra ID logout is handled on client side
    // Token is invalidated when user is removed from app role
    console.log('User logged out from Entra ID');
  }

  getTokenType(): string {
    return 'Bearer';
  }
}
