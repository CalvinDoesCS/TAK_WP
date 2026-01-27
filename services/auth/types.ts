/**
 * Authentication abstraction layer
 * Supports multiple providers: WordPress JWT, Entra ID, etc.
 */

export interface AuthToken {
  accessToken: string;
  refreshToken?: string;
  expiresIn: number;
  tokenType: string;
}

export interface AuthUser {
  id: string;
  email: string;
  name: string;
  roles: string[];
}

export interface AuthProvider {
  /**
   * Authenticate user and get token
   */
  authenticate(username: string, password: string): Promise<AuthToken>;

  /**
   * Validate and decode token
   */
  validateToken(token: string): Promise<AuthUser>;

  /**
   * Refresh expired token
   */
  refreshToken(refreshToken: string): Promise<AuthToken>;

  /**
   * Logout/revoke token
   */
  logout(token: string): Promise<void>;

  /**
   * Get token type (Bearer, etc)
   */
  getTokenType(): string;
}

export interface AuthConfig {
  provider: 'wordpress-jwt' | 'entra-id';
  endpoint: string;
  clientId?: string;
  clientSecret?: string;
  tenantId?: string;
}
