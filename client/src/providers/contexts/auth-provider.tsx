import React, { createContext, useEffect, useState, useCallback, useMemo, ReactNode } from 'react';
import { useNavigate } from 'react-router-dom';
import {
	AUTH_TOKEN_STORAGE_KEY,
	fetchCurrentUser,
	loginSession,
	registerSession,
	revokeSession,
} from '@/api/auth/session';
import { User } from '@/types';

interface AuthContextType {
	user: User | null;
	isAuthenticated: boolean;
	isLoading: boolean;
	sessionExpired: boolean;
	token: string | null;
	login: ({ email, password }: { email: string; password: string }) => Promise<void>;
	register: ({
		name,
		email,
		password,
		password_confirmation,
	}: {
		name: string;
		email: string;
		password: string;
		password_confirmation: string;
	}) => Promise<void>;
	logout: () => Promise<void>;
	clearSessionExpired: () => void;
}

interface AuthProviderProps {
	children: ReactNode;
}

export const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
	const initialToken = useMemo(() => localStorage.getItem(AUTH_TOKEN_STORAGE_KEY), []);
	const [user, setUser] = useState<User | null>(null);
	const [token, setToken] = useState<string | null>(initialToken);
	const [isLoading, setIsLoading] = useState(Boolean(initialToken));
	const [sessionExpired, setSessionExpired] = useState(false);
	const navigate = useNavigate();

	const isAuthenticated = Boolean(user);

	const login = useCallback(async (loginPayload) => {
		const session = await loginSession(loginPayload);

		localStorage.setItem(AUTH_TOKEN_STORAGE_KEY, session.accessToken);
		setToken(session.accessToken);
		setUser(session.user);
		setIsLoading(false);
		setSessionExpired(false);
	}, []);

	const register = useCallback(async (registerPayload) => {
		const session = await registerSession(registerPayload);

		localStorage.setItem(AUTH_TOKEN_STORAGE_KEY, session.accessToken);
		setToken(session.accessToken);
		setUser(session.user);
		setIsLoading(false);
		setSessionExpired(false);
	}, []);

	/**
	 * Dropping the credentials is the part that must never be skipped, so it lives on its own and
	 * every sign-out path ends here regardless of what the server did.
	 */
	const clearSession = useCallback(() => {
		localStorage.removeItem(AUTH_TOKEN_STORAGE_KEY);
		setToken(null);
		setUser(null);
		setIsLoading(false);
		navigate('/login');
	}, [navigate]);

	// `logout` reads the token from storage rather than from state so that a sign-out triggered
	// before the restore effect settles still knows there is something to revoke.
	const logout = useCallback(async () => {
		try {
			if (localStorage.getItem(AUTH_TOKEN_STORAGE_KEY)) {
				await revokeSession();
			}
		} finally {
			clearSession();
		}
	}, [clearSession]);

	const clearSessionExpired = useCallback(() => {
		setSessionExpired(false);
	}, []);

	useEffect(() => {
		// A 401 means the token is already dead, so calling the revocation endpoint with it would
		// only produce a second 401. This path clears locally and skips the server round trip.
		const handleUnauthorized = () => {
			setSessionExpired(true);
			clearSession();
		};

		window.addEventListener('auth:unauthorized', handleUnauthorized);
		return () => window.removeEventListener('auth:unauthorized', handleUnauthorized);
	}, [clearSession]);

	useEffect(() => {
		const handleStorageChange = (e: StorageEvent) => {
			if (e.key === AUTH_TOKEN_STORAGE_KEY && !e.newValue) {
				setToken(null);
				setUser(null);
				setIsLoading(false);
				navigate('/login');
			}

			if (e.key === AUTH_TOKEN_STORAGE_KEY && e.newValue) {
				setToken(e.newValue);
				setUser(null);
				setIsLoading(true);
			}
		};

		window.addEventListener('storage', handleStorageChange);
		return () => window.removeEventListener('storage', handleStorageChange);
	}, [navigate]);

	useEffect(() => {
		if (!isLoading) {
			return;
		}

		if (!token) {
			setUser(null);
			setIsLoading(false);
			return;
		}

		let isActive = true;

		const verifyToken = async () => {
			try {
				const currentUser = await fetchCurrentUser();

				if (!isActive) {
					return;
				}

				setUser(currentUser);
			} catch (error) {
				if (!isActive) {
					return;
				}

				console.error('Auth check failed:', error);
				localStorage.removeItem(AUTH_TOKEN_STORAGE_KEY);
				setToken(null);
				setUser(null);
			} finally {
				if (isActive) {
					setIsLoading(false);
				}
			}
		};

		void verifyToken();

		return () => {
			isActive = false;
		};
	}, [isLoading, token]);

	// TODO:
	// Doublecheck if this context consumption doesnt cause full app re-renders on unwanted occasions.
	// Might need to consider Zustand to be able use State selectors that ensure only selected store value consumer is re-rendered. instead of all the context consuming components.
	const value = useMemo(
		() => ({
			user,
			isAuthenticated,
			isLoading,
			sessionExpired,
			token,
			login,
			register,
			logout,
			clearSessionExpired,
		}),
		[user, isAuthenticated, isLoading, sessionExpired, token, login, register, logout, clearSessionExpired],
	);

	return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};
