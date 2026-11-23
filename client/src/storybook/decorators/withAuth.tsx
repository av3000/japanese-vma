import type * as React from 'react';
import type { Decorator } from '@storybook/react-vite';
import { fn } from 'storybook/test';
import { AuthContext } from '@/providers/contexts/auth-provider';
import { users } from '@/storybook/fixtures/users';

export type AuthValue = NonNullable<React.ComponentProps<typeof AuthContext.Provider>['value']>;

export type AuthScenario = 'guest' | 'loading' | 'member';

/** A scenario name, or fields to override. Passing a `user` signs that user in. */
export type AuthParameter = AuthScenario | Partial<AuthValue>;

/** The auth actions every story gets; cleared before each story, so a `play` can assert on them. */
export const authSpies = {
	login: fn(async () => undefined),
	register: fn(async () => undefined),
	logout: fn(async () => undefined),
	clearSessionExpired: fn(),
};

const guest: AuthValue = {
	user: null,
	isAuthenticated: false,
	isLoading: false,
	sessionExpired: false,
	token: null,
	...authSpies,
};

export const authScenarios: Record<AuthScenario, AuthValue> = {
	guest,
	loading: { ...guest, isLoading: true, token: 'story-token' },
	member: { ...guest, user: users.member, isAuthenticated: true, token: 'story-token' },
};

export const resolveAuth = (parameter: AuthParameter | undefined, scenario: AuthScenario): AuthValue => {
	if (typeof parameter === 'string') {
		return authScenarios[parameter];
	}

	if (!parameter) {
		return authScenarios[scenario];
	}

	return { ...(parameter.user ? authScenarios.member : authScenarios[scenario]), ...parameter };
};

/**
 * Provides `AuthContext` with a plain value: never the real `AuthProvider`, so no request, storage
 * read or navigation. `parameters.auth` wins over the toolbar's `auth` global.
 */
export const withAuth: Decorator = (Story, { parameters, globals }) => (
	<AuthContext.Provider
		value={resolveAuth(parameters.auth as AuthParameter | undefined, (globals.auth as AuthScenario) ?? 'guest')}
	>
		<Story />
	</AuthContext.Provider>
);
