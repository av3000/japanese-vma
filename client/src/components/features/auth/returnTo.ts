import type { Location } from 'react-router-dom';

/** Router state the auth pages read and forward. `PrivateRoute` sets `from` when it bounces a guest. */
export interface AuthLocationState {
	from?: Pick<Location, 'pathname' | 'search' | 'hash'>;
}

const AUTH_PATHS = ['/login', '/register'];

/**
 * Where to go after signing in: the page `PrivateRoute` bounced the user from, or home. `from` lives
 * in router state, which a link from another site cannot set, but anything that is not an in-app
 * path is still refused, and so are the auth pages themselves so a detour cannot loop.
 */
export const resolveReturnTo = (state: unknown): string => {
	const from = (state as AuthLocationState | null | undefined)?.from;
	const pathname = from?.pathname;

	if (typeof pathname !== 'string' || !pathname.startsWith('/') || pathname.startsWith('//')) {
		return '/';
	}

	if (AUTH_PATHS.includes(pathname)) {
		return '/';
	}

	return `${pathname}${from?.search ?? ''}${from?.hash ?? ''}`;
};
