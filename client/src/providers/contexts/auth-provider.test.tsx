import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useAuth } from '@/hooks/useAuth';
import axiosInstance from '@/services/axios';
import { AuthProvider } from './auth-provider';

vi.mock('@/services/axios', () => ({
	default: {
		get: vi.fn(),
		post: vi.fn(),
	},
}));

const snapshots: ReturnType<typeof useAuth>[] = [];

const AuthProbe = () => {
	const auth = useAuth();
	snapshots.push(auth);
	return <span>{auth.isLoading ? 'loading' : 'idle'}</span>;
};

const createLocalStorage = (entries: Record<string, string> = {}) => {
	const storage = new Map(Object.entries(entries));

	return {
		getItem: vi.fn((key: string) => storage.get(key) ?? null),
		setItem: vi.fn((key: string, value: string) => {
			storage.set(key, value);
		}),
		removeItem: vi.fn((key: string) => {
			storage.delete(key);
		}),
		clear: vi.fn(() => {
			storage.clear();
		}),
	} as unknown as Storage;
};

const renderAuthProvider = () =>
	renderToStaticMarkup(
		<MemoryRouter>
			<AuthProvider>
				<AuthProbe />
			</AuthProvider>
		</MemoryRouter>,
	);

describe('AuthProvider startup state', () => {
	beforeEach(() => {
		snapshots.length = 0;
		vi.mocked(axiosInstance.get).mockReset();
	});

	afterEach(() => {
		vi.unstubAllGlobals();
	});

	it('initializes anonymous when no token is present', () => {
		vi.stubGlobal('localStorage', createLocalStorage());

		renderAuthProvider();

		expect(snapshots.at(-1)?.isAuthenticated).toBe(false);
		expect(snapshots.at(-1)?.isLoading).toBe(false);
		expect(snapshots.at(-1)?.token).toBeNull();
		expect(axiosInstance.get).not.toHaveBeenCalled();
	});

	it('initializes as loading when a token is present', () => {
		vi.stubGlobal('localStorage', createLocalStorage({ token: 'stored-token' }));

		renderAuthProvider();

		expect(snapshots.at(-1)?.isAuthenticated).toBe(false);
		expect(snapshots.at(-1)?.isLoading).toBe(true);
		expect(snapshots.at(-1)?.token).toBe('stored-token');
	});
});
