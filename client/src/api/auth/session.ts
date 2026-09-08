import { authLogin, authLogout, authMe, authRegister } from '@/api/generated/auth/auth';
import type { AuthUserResource } from '@/api/generated/model/authUserResource';
import type { LoginRequest } from '@/api/generated/model/loginRequest';
import type { RegisterRequest } from '@/api/generated/model/registerRequest';
import { User } from '@/types';

export const AUTH_TOKEN_STORAGE_KEY = 'token';

export interface AuthSession {
	user: User;
	accessToken: string;
}

/**
 * The one place the wire shape of an authenticated user turns into the UI shape.
 *
 * `AuthUserResource` speaks snake_case, exposes roles as full objects, and carries the token pair
 * that only login/register responses populate. None of that belongs on `User`, so the credential
 * fields are deliberately dropped here instead of being spread into app state.
 */
export const mapAuthUser = (resource: AuthUserResource): User => ({
	id: resource.id,
	uuid: resource.uuid,
	name: resource.name,
	email: resource.email,
	roles: (resource.roles ?? []).map((role) => role.name),
	isAdmin: resource.is_admin,
	created_at: resource.created_at,
});

/**
 * Login and register return the same resource, so both funnel through one mapper. The token is
 * lifted out separately because it is a transport credential, not a user attribute.
 */
const toSession = (resource: AuthUserResource): AuthSession => {
	// The token pair is optional on the contract because `me` omits it. On a login or register
	// response its absence is a broken server, and failing loudly beats persisting an empty string
	// that would go out as a `Bearer ` header on every later request.
	if (!resource.access_token) {
		throw new Error('Authentication succeeded but no access token was returned.');
	}

	return {
		user: mapAuthUser(resource),
		accessToken: resource.access_token,
	};
};

export const loginSession = async (credentials: LoginRequest): Promise<AuthSession> => {
	const response = await authLogin(credentials);

	return toSession(response.data);
};

export const registerSession = async (details: RegisterRequest): Promise<AuthSession> => {
	const response = await authRegister(details);

	return toSession(response.data);
};

export const fetchCurrentUser = async (signal?: AbortSignal): Promise<User> => {
	const response = await authMe(undefined, signal);

	return mapAuthUser(response.data);
};

/**
 * Best-effort server-side revocation. The caller clears local credentials either way, so a failure
 * here must never surface as a rejected logout — an unreachable API or an already-dead token should
 * still leave the browser signed out.
 */
export const revokeSession = async (): Promise<boolean> => {
	try {
		await authLogout();

		return true;
	} catch (error) {
		console.error('Logout revocation failed:', error);

		return false;
	}
};
