import { AuthConfig } from './types';

/**
 * AuthConfig - Loads and validates authentication configuration
 * Reads from environment variables with sensible defaults
 */
export class AuthConfigLoader {
  static load(): AuthConfig {
    const provider = (process.env.VITE_AUTH_PROVIDER || 'wordpress-jwt') as
      | 'wordpress-jwt'
      | 'entra-id';

    const baseConfig: AuthConfig = {
      provider,
      endpoint: process.env.VITE_AUTH_ENDPOINT || 'http://localhost:8080',
    };

    // Provider-specific configuration
    if (provider === 'wordpress-jwt') {
      return {
        ...baseConfig,
        endpoint: process.env.VITE_AUTH_ENDPOINT || 'http://localhost:8080',
      };
    }

    if (provider === 'entra-id') {
      const requiredFields = ['VITE_AUTH_CLIENT_ID', 'VITE_AUTH_TENANT_ID'];
      const missing = requiredFields.filter(
        (field) => !process.env[field]
      );

      if (missing.length > 0) {
        throw new Error(
          `Missing required Entra ID configuration: ${missing.join(', ')}`
        );
      }

      return {
        ...baseConfig,
        provider: 'entra-id',
        endpoint: process.env.VITE_AUTH_ENDPOINT || 'https://graph.microsoft.com',
        clientId: process.env.VITE_AUTH_CLIENT_ID || '',
        clientSecret: process.env.VITE_AUTH_CLIENT_SECRET || '',
        tenantId: process.env.VITE_AUTH_TENANT_ID || '',
      };
    }

    throw new Error(`Unknown auth provider: ${provider}`);
  }

  static validate(config: AuthConfig): boolean {
    if (!config.provider) {
      throw new Error('Auth provider not specified');
    }

    if (!config.endpoint) {
      throw new Error('Auth endpoint not specified');
    }

    if (config.provider === 'entra-id') {
      if (!config.clientId || !config.tenantId) {
        throw new Error('Entra ID requires clientId and tenantId');
      }
    }

    return true;
  }
}
