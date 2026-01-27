import { useState, useCallback, useEffect } from 'react';
import { AuthFactory } from './factory';
import { AuthConfigLoader } from './config';
import { AuthToken, AuthUser } from './types';

interface UseAuthReturn {
  user: AuthUser | null;
  token: AuthToken | null;
  isLoading: boolean;
  error: Error | null;
  login: (username: string, password: string) => Promise<void>;
  logout: () => Promise<void>;
  validateSession: () => Promise<boolean>;
  refreshToken: () => Promise<void>;
  isAuthenticated: boolean;
}

/**
 * useAuth Hook - React hook for authentication
 * Usage in components:
 *   const { user, login, logout, isAuthenticated } = useAuth();
 *
 *   if (!isAuthenticated) {
 *     return <LoginForm onLogin={login} />;
 *   }
 *
 *   return <Dashboard user={user} />;
 */
export function useAuth(): UseAuthReturn {
  const [user, setUser] = useState<AuthUser | null>(null);
  const [token, setToken] = useState<AuthToken | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<Error | null>(null);

  // Initialize auth on mount
  useEffect(() => {
    const initAuth = async () => {
      try {
        const storedToken = localStorage.getItem('authToken');
        if (storedToken) {
          const parsed = JSON.parse(storedToken);
          const authConfig = AuthConfigLoader.load();
          const provider = AuthFactory.create(authConfig);

          const userData = await provider.validateToken(parsed.accessToken);
          setUser(userData);
          setToken(parsed);
        }
      } catch (err) {
        setError(err instanceof Error ? err : new Error('Auth init failed'));
        localStorage.removeItem('authToken');
      } finally {
        setIsLoading(false);
      }
    };

    initAuth();
  }, []);

  const login = useCallback(
    async (username: string, password: string) => {
      setIsLoading(true);
      setError(null);

      try {
        const authConfig = AuthConfigLoader.load();
        const provider = AuthFactory.create(authConfig);

        const authToken = await provider.authenticate(username, password);
        const userData = await provider.validateToken(authToken.accessToken);

        setToken(authToken);
        setUser(userData);

        // Persist token in localStorage
        localStorage.setItem('authToken', JSON.stringify(authToken));
      } catch (err) {
        const error = err instanceof Error ? err : new Error('Login failed');
        setError(error);
        throw error;
      } finally {
        setIsLoading(false);
      }
    },
    []
  );

  const logout = useCallback(async () => {
    setIsLoading(true);
    try {
      if (token) {
        const authConfig = AuthConfigLoader.load();
        const provider = AuthFactory.create(authConfig);
        await provider.logout(token.accessToken);
      }

      setUser(null);
      setToken(null);
      localStorage.removeItem('authToken');
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Logout failed'));
    } finally {
      setIsLoading(false);
    }
  }, [token]);

  const validateSession = useCallback(async () => {
    if (!token) return false;

    try {
      const authConfig = AuthConfigLoader.load();
      const provider = AuthFactory.create(authConfig);
      await provider.validateToken(token.accessToken);
      return true;
    } catch {
      setUser(null);
      setToken(null);
      localStorage.removeItem('authToken');
      return false;
    }
  }, [token]);

  const refreshToken = useCallback(async () => {
    if (!token || !token.refreshToken) {
      throw new Error('No refresh token available');
    }

    try {
      const authConfig = AuthConfigLoader.load();
      const provider = AuthFactory.create(authConfig);
      const newToken = await provider.refreshToken(token.refreshToken);

      setToken(newToken);
      localStorage.setItem('authToken', JSON.stringify(newToken));
    } catch (err) {
      setUser(null);
      setToken(null);
      localStorage.removeItem('authToken');
      throw err;
    }
  }, [token]);

  return {
    user,
    token,
    isLoading,
    error,
    login,
    logout,
    validateSession,
    refreshToken,
    isAuthenticated: !!user && !!token,
  };
}
