import type { User } from '@/types';

/**
 * Story viewers. `member` is the default signed-in user; `other` is a second member for
 * owner-versus-visitor stories. Override `id` to make either the owner of a fixture.
 */
export const users = {
	member: {
		id: 3,
		uuid: 'user-member',
		name: 'Hanako',
		email: 'hanako@example.com',
		roles: [],
		isAdmin: false,
	},
	other: {
		id: 42,
		uuid: 'user-other',
		name: 'Kenji',
		email: 'kenji@example.com',
		roles: [],
		isAdmin: false,
	},
} satisfies Record<string, User>;
