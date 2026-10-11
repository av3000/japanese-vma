import type * as React from 'react';
import type { AuthContext } from '@/providers/contexts/auth-provider';

export type AuthValue = NonNullable<React.ComponentProps<typeof AuthContext.Provider>['value']>;

const noop = async () => undefined;

/** An `AuthContext` value for a visitor who is not signed in. */
export const guestAuth: AuthValue = {
	user: null,
	isAuthenticated: false,
	isLoading: false,
	sessionExpired: false,
	token: null,
	login: noop,
	register: noop,
	logout: noop,
	clearSessionExpired: () => undefined,
};

/** An `AuthContext` value for a signed-in member with the given id. */
export const signedInAuth = (id: number, isAdmin = false): AuthValue => ({
	...guestAuth,
	isAuthenticated: true,
	token: 'story-token',
	user: { id, uuid: `user-${id}`, name: 'Hanako', email: 'hanako@example.com', roles: [], isAdmin },
});
