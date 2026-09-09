import { beforeEach, describe, expect, it, vi } from 'vitest';
import { authLogin, authLogout, authMe } from '@/api/generated/auth/auth';
import type { AuthUserResource } from '@/api/generated/model/authUserResource';
import { fetchCurrentUser, loginSession, mapAuthUser, revokeSession } from './session';

vi.mock('@/api/generated/auth/auth', () => ({
	authLogin: vi.fn(),
	authRegister: vi.fn(),
	authMe: vi.fn(),
	authLogout: vi.fn(),
}));

const resource: AuthUserResource = {
	id: 7,
	uuid: 'user-uuid',
	name: 'Sora',
	email: 'sora@example.com',
	roles: [
		{ name: 'admin', guard_name: 'api', permissions: ['articles.moderate'], is_system_role: true },
		{ name: 'editor', guard_name: 'api', permissions: [], is_system_role: false },
	],
	is_admin: true,
	created_at: '2026-01-02T03:04:05+00:00',
};

describe('mapAuthUser', () => {
	beforeEach(() => {
		vi.spyOn(console, 'error').mockImplementation(() => {});
	});

	it('flattens roles to names and renames the admin flag', () => {
		expect(mapAuthUser(resource)).toEqual({
			id: 7,
			uuid: 'user-uuid',
			name: 'Sora',
			email: 'sora@example.com',
			roles: ['admin', 'editor'],
			isAdmin: true,
			created_at: '2026-01-02T03:04:05+00:00',
		});
	});

	it('keeps transport credentials out of the user object', () => {
		const mapped = mapAuthUser({ ...resource, access_token: 'secret-token', token_type: 'Bearer' });

		expect(mapped).not.toHaveProperty('access_token');
		expect(mapped).not.toHaveProperty('token_type');
		expect(mapped).not.toHaveProperty('is_admin');
	});
});

describe('loginSession', () => {
	it('splits the response into a user and a token', async () => {
		vi.mocked(authLogin).mockResolvedValue({
			success: true,
			data: { ...resource, access_token: 'fresh-token', token_type: 'Bearer' },
		});

		await expect(loginSession({ email: 'sora@example.com', password: 'secret' })).resolves.toEqual({
			user: mapAuthUser(resource),
			accessToken: 'fresh-token',
		});
	});

	it('fails loudly when the server omits the token', async () => {
		vi.mocked(authLogin).mockResolvedValue({ success: true, data: resource });

		await expect(loginSession({ email: 'sora@example.com', password: 'secret' })).rejects.toThrow(
			/no access token/i,
		);
	});
});

describe('fetchCurrentUser', () => {
	it('maps a token-less me payload', async () => {
		vi.mocked(authMe).mockResolvedValue({ success: true, data: resource });

		await expect(fetchCurrentUser()).resolves.toEqual(mapAuthUser(resource));
	});
});

describe('revokeSession', () => {
	it('reports success when the server revokes the token', async () => {
		vi.mocked(authLogout).mockResolvedValue({ success: true, message: 'Successfully logged out' });

		await expect(revokeSession()).resolves.toBe(true);
	});

	it('swallows a failed revocation so local cleanup can still run', async () => {
		vi.spyOn(console, 'error').mockImplementation(() => {});
		vi.mocked(authLogout).mockRejectedValue(new Error('Network down'));

		await expect(revokeSession()).resolves.toBe(false);
	});
});
