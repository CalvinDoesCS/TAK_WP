import { AuthProvider, AuthConfig } from './types';
import { WordPressJWTProvider } from './wordpress-jwt-provider';
import { EntraIDProvider } from './entra-id-provider';

/**
 * AuthFactory - Creates the appropriate auth provider based on configuration
 * Usage:
 *   const authProvider = AuthFactory.create(config);
 *   const token = await authProvider.authenticate('user', 'pass');
 */
export class AuthFactory {
  static create(config: AuthConfig): AuthProvider {
    switch (config.provider) {
      case 'wordpress-jwt':
        return new WordPressJWTProvider(config);
      case 'entra-id':
        return new EntraIDProvider(config);
      default:
        throw new Error(`Unknown auth provider: ${config.provider}`);
    }
  }
}
