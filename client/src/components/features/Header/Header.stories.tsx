import type * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { AuthContext } from '@/providers/contexts/auth-provider';
import { DEFAULT_SOCKET_CONTEXT, SocketContext } from '@/providers/contexts/socket-provider';
import Header from './';

type AuthValue = NonNullable<React.ComponentProps<typeof AuthContext.Provider>['value']>;

const noop = async () => undefined;

const guest: AuthValue = {
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

const loading: AuthValue = { ...guest, isLoading: true };

const signedIn: AuthValue = {
	...guest,
	isAuthenticated: true,
	token: 'story-token',
	user: { id: 1, uuid: 'story-user', name: 'Hanako', email: 'hanako@example.com', roles: [], isAdmin: false },
};

const connectedSocket = {
	...DEFAULT_SOCKET_CONTEXT,
	isConfigured: true,
	connectionStatus: 'connected' as const,
	hasAttemptedConnection: true,
};

/** The Header reads auth and socket state from context; the story supplies both. */
const HeaderWithAuth: React.FC<{ auth: AuthValue }> = ({ auth }) => (
	<AuthContext.Provider value={auth}>
		<SocketContext.Provider value={connectedSocket}>
			<Header />
		</SocketContext.Provider>
	</AuthContext.Provider>
);

const meta = {
	title: 'Features/Header',
	component: HeaderWithAuth,
	parameters: { layout: 'fullscreen' },
	args: { auth: guest },
	argTypes: { auth: { control: false } },
} satisfies Meta<typeof HeaderWithAuth>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Guest: Story = {};

export const Loading: Story = { args: { auth: loading } };

/** The widest state: eight links, "+ New", the socket indicator, the user name and Logout. */
export const SignedIn: Story = { args: { auth: signedIn } };
