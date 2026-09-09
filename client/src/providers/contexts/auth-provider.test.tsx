// @vitest-environment jsdom
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { authLogin, authLogout, authMe, authRegister } from '@/api/generated/auth/auth';
import type { AuthUserResource } from '@/api/generated/model/authUserResource';
import { useAuth } from '@/hooks/useAuth';
import { renderWithAct } from '@/test/renderWithAct';
import { AuthProvider } from './auth-provider';

const navigate = vi.fn();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return { ...actual, useNavigate: () => navigate };
});

// Mocking the generated module rather than the session adapter keeps the mapper under test: a
// regression that leaks `is_admin` or a role object into `User` still fails here.
vi.mock('@/api/generated/auth/auth', () => ({
	authLogin: vi.fn(),
	authRegister: vi.fn(),
	authMe: vi.fn(),
	authLogout: vi.fn(),
}));

const authUser = (overrides: Partial<AuthUserResource> = {}): AuthUserResource => ({
	id: 7,
	uuid: 'user-uuid',
	name: 'Sora',
	email: 'sora@example.com',
	roles: [{ name: 'admin', guard_name: 'api', permissions: ['articles.moderate'], is_system_role: true }],
	is_admin: true,
	created_at: '2026-01-02T03:04:05+00:00',
	...overrides,
});

const snapshots: ReturnType<typeof useAuth>[] = [];

const latest = () => {
	const snapshot = snapshots.at(-1);

	if (!snapshot) {
		throw new Error('AuthProvider never rendered its consumer.');
	}

	return snapshot;
};

const AuthProbe = () => {
	const auth = useAuth();
	snapshots.push(auth);
	return <span>{auth.isLoading ? 'loading' : 'idle'}</span>;
};

const renderAuthProvider = () =>
	renderWithAct(
		<MemoryRouter>
			<AuthProvider>
				<AuthProbe />
			</AuthProvider>
		</MemoryRouter>,
	);

describe('AuthProvider', () => {
	beforeEach(() => {
		snapshots.length = 0;
		navigate.mockClear();
		vi.mocked(authLogin).mockReset();
		vi.mocked(authRegister).mockReset();
		vi.mocked(authMe).mockReset();
		vi.mocked(authLogout).mockReset();
		localStorage.clear();
		vi.spyOn(console, 'error').mockImplementation(() => {});
	});

	afterEach(() => {
		vi.restoreAllMocks();
	});

	describe('startup', () => {
		it('initializes anonymous without calling the API when no token is stored', async () => {
			const view = await renderAuthProvider();

			expect(latest().isAuthenticated).toBe(false);
			expect(latest().isLoading).toBe(false);
			expect(latest().token).toBeNull();
			expect(authMe).not.toHaveBeenCalled();

			await view.unmount();
		});

		it('restores the session from a valid stored token', async () => {
			localStorage.setItem('token', 'stored-token');
			vi.mocked(authMe).mockResolvedValue({ success: true, data: authUser() });

			const view = await renderAuthProvider();

			expect(authMe).toHaveBeenCalledTimes(1);
			expect(latest().isAuthenticated).toBe(true);
			expect(latest().isLoading).toBe(false);
			expect(latest().token).toBe('stored-token');
			// The mapper owns the wire-to-UI translation: camelCase admin flag, role names only, and
			// no credential fields riding along on the user object.
			expect(latest().user).toEqual({
				id: 7,
				uuid: 'user-uuid',
				name: 'Sora',
				email: 'sora@example.com',
				roles: ['admin'],
				isAdmin: true,
				created_at: '2026-01-02T03:04:05+00:00',
			});

			await view.unmount();
		});

		it('discards a stored token the server rejects', async () => {
			localStorage.setItem('token', 'stale-token');
			vi.mocked(authMe).mockRejectedValue(new Error('Unauthenticated.'));

			const view = await renderAuthProvider();

			expect(latest().isAuthenticated).toBe(false);
			expect(latest().isLoading).toBe(false);
			expect(latest().token).toBeNull();
			expect(localStorage.getItem('token')).toBeNull();

			await view.unmount();
		});
	});

	describe('login and register', () => {
		it('signs in through the generated login client', async () => {
			vi.mocked(authLogin).mockResolvedValue({
				success: true,
				data: authUser({ access_token: 'fresh-token', token_type: 'Bearer' }),
			});

			const view = await renderAuthProvider();
			await view.flush(() => latest().login({ email: 'sora@example.com', password: 'secret' }));

			expect(authLogin).toHaveBeenCalledWith({ email: 'sora@example.com', password: 'secret' });
			expect(localStorage.getItem('token')).toBe('fresh-token');
			expect(latest().token).toBe('fresh-token');
			expect(latest().isAuthenticated).toBe(true);
			expect(latest().user?.isAdmin).toBe(true);
			expect(latest().sessionExpired).toBe(false);

			await view.unmount();
		});

		it('registers through the generated register client', async () => {
			vi.mocked(authRegister).mockResolvedValue({
				success: true,
				data: authUser({ id: 11, is_admin: false, roles: [], access_token: 'new-token', token_type: 'Bearer' }),
			});

			const view = await renderAuthProvider();
			await view.flush(() =>
				latest().register({
					name: 'Sora',
					email: 'sora@example.com',
					password: 'secret',
					password_confirmation: 'secret',
				}),
			);

			expect(authRegister).toHaveBeenCalledWith({
				name: 'Sora',
				email: 'sora@example.com',
				password: 'secret',
				password_confirmation: 'secret',
			});
			expect(localStorage.getItem('token')).toBe('new-token');
			expect(latest().user).toMatchObject({ id: 11, isAdmin: false, roles: [] });

			await view.unmount();
		});

		it('propagates a rejected login without touching stored credentials', async () => {
			const failure = Object.assign(new Error('Request failed'), {
				response: { status: 401, data: { message: 'Invalid email or password' } },
			});
			vi.mocked(authLogin).mockRejectedValue(failure);

			const view = await renderAuthProvider();
			await expect(
				view.flush(() => latest().login({ email: 'sora@example.com', password: 'wrong' })),
			).rejects.toBe(failure);

			expect(localStorage.getItem('token')).toBeNull();
			expect(latest().isAuthenticated).toBe(false);

			await view.unmount();
		});
	});

	describe('logout', () => {
		const signedIn = async () => {
			localStorage.setItem('token', 'stored-token');
			vi.mocked(authMe).mockResolvedValue({ success: true, data: authUser() });

			return renderAuthProvider();
		};

		it('revokes the token on the server and clears the local session', async () => {
			vi.mocked(authLogout).mockResolvedValue({ success: true, message: 'Successfully logged out' });

			const view = await signedIn();
			await view.flush(() => latest().logout());

			expect(authLogout).toHaveBeenCalledTimes(1);
			expect(localStorage.getItem('token')).toBeNull();
			expect(latest().token).toBeNull();
			expect(latest().isAuthenticated).toBe(false);
			expect(navigate).toHaveBeenCalledWith('/login');

			await view.unmount();
		});

		it('still clears the local session when revocation fails', async () => {
			vi.mocked(authLogout).mockRejectedValue(new Error('Network down'));

			const view = await signedIn();
			await view.flush(() => latest().logout());

			expect(authLogout).toHaveBeenCalledTimes(1);
			expect(localStorage.getItem('token')).toBeNull();
			expect(latest().isAuthenticated).toBe(false);
			expect(navigate).toHaveBeenCalledWith('/login');

			await view.unmount();
		});

		it('skips revocation when there is no token to revoke', async () => {
			const view = await renderAuthProvider();
			await view.flush(() => latest().logout());

			expect(authLogout).not.toHaveBeenCalled();
			expect(navigate).toHaveBeenCalledWith('/login');

			await view.unmount();
		});
	});

	describe('session invalidation', () => {
		it('clears the session locally on an unauthorized event without a second server call', async () => {
			localStorage.setItem('token', 'stored-token');
			vi.mocked(authMe).mockResolvedValue({ success: true, data: authUser() });

			const view = await renderAuthProvider();
			await view.flush(() => {
				window.dispatchEvent(new CustomEvent('auth:unauthorized'));
			});

			expect(latest().sessionExpired).toBe(true);
			expect(latest().isAuthenticated).toBe(false);
			expect(localStorage.getItem('token')).toBeNull();
			expect(navigate).toHaveBeenCalledWith('/login');
			// The token that produced the 401 is already dead; revoking it would just 401 again.
			expect(authLogout).not.toHaveBeenCalled();

			await view.unmount();
		});

		it('clears sessionExpired on request', async () => {
			const view = await renderAuthProvider();
			await view.flush(() => {
				window.dispatchEvent(new CustomEvent('auth:unauthorized'));
			});
			expect(latest().sessionExpired).toBe(true);

			await view.flush(() => latest().clearSessionExpired());

			expect(latest().sessionExpired).toBe(false);

			await view.unmount();
		});
	});

	describe('cross-tab storage sync', () => {
		it('signs out when another tab removes the token', async () => {
			localStorage.setItem('token', 'stored-token');
			vi.mocked(authMe).mockResolvedValue({ success: true, data: authUser() });

			const view = await renderAuthProvider();
			expect(latest().isAuthenticated).toBe(true);

			await view.flush(() => {
				window.dispatchEvent(new StorageEvent('storage', { key: 'token', newValue: null }));
			});

			expect(latest().isAuthenticated).toBe(false);
			expect(latest().token).toBeNull();
			expect(navigate).toHaveBeenCalledWith('/login');

			await view.unmount();
		});

		it('re-verifies against the API when another tab signs in', async () => {
			const view = await renderAuthProvider();
			expect(authMe).not.toHaveBeenCalled();

			vi.mocked(authMe).mockResolvedValue({ success: true, data: authUser({ id: 42, is_admin: false }) });
			await view.flush(() => {
				window.dispatchEvent(new StorageEvent('storage', { key: 'token', newValue: 'other-tab-token' }));
			});

			expect(authMe).toHaveBeenCalledTimes(1);
			expect(latest().token).toBe('other-tab-token');
			expect(latest().user).toMatchObject({ id: 42, isAdmin: false });

			await view.unmount();
		});
	});
});
