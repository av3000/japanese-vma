import type * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, fn, userEvent, waitFor, within } from '@storybook/test';
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

const signedInAs = (name: string): AuthValue => ({
	...guest,
	isAuthenticated: true,
	token: 'story-token',
	user: { id: 1, uuid: 'story-user', name, email: 'hanako@example.com', roles: [], isAdmin: false },
});

const connectedSocket = {
	...DEFAULT_SOCKET_CONTEXT,
	isConfigured: true,
	connectionStatus: 'connected' as const,
	hasAttemptedConnection: true,
};

/** The Header reads auth and socket state from context; the story supplies both. */
const HeaderWithAuth: React.FC<{ auth: AuthValue; onLogout?: () => void }> = ({ auth, onLogout }) => (
	<AuthContext.Provider value={onLogout ? { ...auth, logout: async () => onLogout() } : auth}>
		<SocketContext.Provider value={connectedSocket}>
			<Header />
		</SocketContext.Provider>
	</AuthContext.Provider>
);

/**
 * The play functions follow whichever layout the canvas is wide enough for: the bar from 1024px,
 * the drawers below (the Vitest browser runs at a phone width).
 */
const isDesktop = () => window.matchMedia('(min-width: 1024px)').matches;

const openMenuDrawer = async (canvasElement: HTMLElement) => {
	await userEvent.click(within(canvasElement).getByRole('button', { name: 'Open navigation' }));
	return waitFor(() => {
		const dialog = canvasElement.querySelector<HTMLDialogElement>('dialog[aria-label="Navigation"]');
		expect(dialog?.open).toBe(true);
		return within(dialog as HTMLDialogElement);
	});
};

const meta = {
	title: 'Features/Header',
	component: HeaderWithAuth,
	parameters: { layout: 'fullscreen' },
	args: { auth: guest, onLogout: fn() },
	argTypes: { auth: { control: false } },
} satisfies Meta<typeof HeaderWithAuth>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Guest: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.queryByRole('menu')).toBeNull();

		if (isDesktop()) {
			const account = canvas.getByRole('list', { name: 'Account' });
			await expect(within(account).getByRole('link', { name: 'Log In' })).toHaveAttribute('href', '/login');
			await expect(within(account).getByRole('link', { name: 'Sign Up' })).toHaveAttribute('href', '/register');
			return;
		}

		const drawer = await openMenuDrawer(canvasElement);
		await expect(drawer.getByRole('link', { name: 'Sign Up' })).toHaveAttribute('href', '/register');
		await expect(drawer.getByRole('link', { name: 'Log In' })).toHaveAttribute('href', '/login');
		await userEvent.click(drawer.getByRole('button', { name: 'Close navigation' }));
	},
};

export const Loading: Story = {
	args: { auth: loading },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).queryByRole('link', { name: 'Sign Up' })).toBeNull();
	},
};

/** A signed-in user sees only their name; Dashboard and Log out sit behind it. */
export const SignedIn: Story = {
	args: { auth: signedInAs('Hanako') },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		if (isDesktop()) {
			const account = canvas.getByRole('list', { name: 'Account' });
			const button = within(account).getByRole('button', { name: 'Hanako' });
			await expect(button).toHaveAttribute('aria-expanded', 'false');
			await userEvent.click(button);
			await expect(button).toHaveAttribute('aria-expanded', 'true');
			await expect(within(account).getByRole('link', { name: 'Dashboard' })).toHaveAttribute(
				'href',
				'/dashboard',
			);
			await userEvent.click(within(account).getByRole('button', { name: 'Log out' }));
			await expect(args.onLogout).toHaveBeenCalled();
			return;
		}

		const drawer = await openMenuDrawer(canvasElement);
		await expect(drawer.getByRole('heading', { name: 'Explore' })).toBeInTheDocument();
		await expect(drawer.getByRole('heading', { name: 'Dictionary' })).toBeInTheDocument();
		await expect(drawer.getByRole('link', { name: 'Kanji' })).toHaveAttribute('href', '/kanjis');
		const button = drawer.getByRole('button', { name: 'Hanako' });
		await userEvent.click(button);
		await expect(drawer.getByRole('link', { name: 'Dashboard' })).toHaveAttribute('href', '/dashboard');
		await expect(drawer.getByRole('button', { name: 'Log out' })).toBeInTheDocument();

		const trigger = canvas.getByRole('button', { name: 'Open navigation' });
		await userEvent.keyboard('{Escape}');
		await waitFor(() => expect(trigger).toHaveFocus());
	},
};

/** The Explore disclosure on desktop, with a description under each link. */
export const ExploreOpen: Story = {
	args: { auth: signedInAs('Hanako') },
	play: async ({ canvasElement }) => {
		if (!isDesktop()) return;
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Explore' }));
		await expect(canvas.getByRole('link', { name: /^Articles/ })).toHaveAttribute('href', '/articles');
	},
};

/** A 20-character name, for checking the bar still fits; the name truncates past 12rem. */
export const LongName: Story = { args: { auth: signedInAs('Hanako Yamamoto-Ito') } };
