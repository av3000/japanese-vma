import React, { createContext, useEffect, useState, useCallback, useMemo, ReactNode } from 'react';
import { useNavigate } from 'react-router-dom';
import axiosInstance from '@/services/axios';
import { User } from '@/types';

interface AuthContextType {
	user: User | null;
	isAuthenticated: boolean;
	isLoading: boolean;
	sessionExpired: boolean;
	login: ({ email, password }: { email: string; password: string }) => Promise<void>;
	register: ({
		name,
		email,
		password,
		passwordConfirmation,
	}: {
		name: string;
		email: string;
		password: string;
		passwordConfirmation: string;
	}) => Promise<void>;
	logout: () => void;
	clearSessionExpired: () => void;
}

interface AuthProviderProps {
	children: ReactNode;
}

export const AuthContext = createContext<AuthContextType | undefined>(undefined);

export const AuthProvider: React.FC<AuthProviderProps> = ({ children }) => {
	const [user, setUser] = useState<User | null>(null);
	const [isLoading, setIsLoading] = useState(true);
	const [sessionExpired, setSessionExpired] = useState(false);
	const navigate = useNavigate();

	const isAuthenticated = !!user;

	const checkAuth = useCallback(async () => {
		const token = localStorage.getItem('token');

		if (!token) {
			setIsLoading(false);
			return;
		}

		try {
			const response = await axiosInstance.get('/v1/me');
			setUser({ isAdmin: response.data.data.is_admin, ...response.data.data });
		} catch (error) {
			console.error('Auth check failed:', error);
			localStorage.removeItem('token');
			setUser(null);
		} finally {
			setIsLoading(false);
		}
	}, []);

	const login = useCallback(async (loginPayload) => {
		const response = await axiosInstance.post('/v1/login', loginPayload);
		const { access_token, token_type, ...userData } = response.data.data;

		localStorage.setItem('token', access_token);
		setUser({ isAdmin: userData.is_admin, ...userData });
		setSessionExpired(false);
	}, []);

	const register = useCallback(async (registerPayload) => {
		const response = await axiosInstance.post('/v1/register', registerPayload);
		const { access_token, token_type, ...userData } = response.data.data;

		localStorage.setItem('token', access_token);
		setUser({ isAdmin: userData.is_admin, ...userData });
	}, []);

	const logout = useCallback(() => {
		localStorage.removeItem('token');
		setUser(null);
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
				setUser(null);
				navigate('/login');
			}
		};

		window.addEventListener('storage', handleStorageChange);
		return () => window.removeEventListener('storage', handleStorageChange);
	}, [navigate]);

	useEffect(() => {
		checkAuth();
	}, [checkAuth]);

	const value = useMemo(
		() => ({
			user,
			isAuthenticated,
			isLoading,
			sessionExpired,
			login,
			register,
			logout,
			clearSessionExpired,
		}),
		[user, isAuthenticated, isLoading, sessionExpired, login, register, logout, clearSessionExpired],
	);

	return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};
