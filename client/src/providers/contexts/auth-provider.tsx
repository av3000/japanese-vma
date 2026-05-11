import React, { createContext, useEffect, useState, useCallback, useMemo, ReactNode } from 'react';
import { useNavigate } from 'react-router-dom';
import axiosInstance from '@/services/axios';
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
	logout: () => void;
	clearSessionExpired: () => void;
}

interface AuthProviderProps {
	children: ReactNode;
}

export const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
	const initialToken = useMemo(() => localStorage.getItem('token'), []);
	const [user, setUser] = useState<User | null>(null);
	const [token, setToken] = useState<string | null>(initialToken);
	const [isLoading, setIsLoading] = useState(Boolean(initialToken));
	const [sessionExpired, setSessionExpired] = useState(false);
	const navigate = useNavigate();

	const isAuthenticated = Boolean(user);

	const login = useCallback(async (loginPayload) => {
		const response = await axiosInstance.post('/v1/login', loginPayload);
		const { access_token, ...userData } = response.data.data;

		localStorage.setItem('token', access_token);
		setToken(access_token);
		setUser({ isAdmin: userData.is_admin, ...userData });
		setIsLoading(false);
		setSessionExpired(false);
	}, []);

	const register = useCallback(async (registerPayload) => {
		const response = await axiosInstance.post('/v1/register', registerPayload);
		const { access_token, ...userData } = response.data.data;

		localStorage.setItem('token', access_token);
		setToken(access_token);
		setUser({ isAdmin: userData.is_admin, ...userData });
		setIsLoading(false);
		setSessionExpired(false);
	}, []);

	const logout = useCallback(() => {
		localStorage.removeItem('token');
		setToken(null);
		setUser(null);
		setIsLoading(false);
		navigate('/login');
	}, [navigate]);

	const clearSessionExpired = useCallback(() => {
		setSessionExpired(false);
	}, []);

	useEffect(() => {
		const handleUnauthorized = () => {
			setSessionExpired(true);
			logout();
		};

		window.addEventListener('auth:unauthorized', handleUnauthorized);
		return () => window.removeEventListener('auth:unauthorized', handleUnauthorized);
	}, [logout]);

	useEffect(() => {
		const handleStorageChange = (e: StorageEvent) => {
			if (e.key === 'token' && !e.newValue) {
				setToken(null);
				setUser(null);
				setIsLoading(false);
				navigate('/login');
			}

			if (e.key === 'token' && e.newValue) {
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
				const response = await axiosInstance.get('/v1/me');

				if (!isActive) {
					return;
				}

				setUser({ isAdmin: response.data.data.is_admin, ...response.data.data });
			} catch (error) {
				if (!isActive) {
					return;
				}

				console.error('Auth check failed:', error);
				localStorage.removeItem('token');
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
