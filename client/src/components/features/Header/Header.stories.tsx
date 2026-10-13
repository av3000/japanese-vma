import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { authSpies, type AuthParameter } from '@/storybook/decorators';
import { users } from '@/storybook/fixtures/users';
import Header from './';

const signedInAs = (name: string): AuthParameter => ({ user: { ...users.member, name } });

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
	title: 'Features/Navigation/Header',
	component: Header,
	parameters: { layout: 'fullscreen' },
} satisfies Meta<typeof Header>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Guest: Story = {
	parameters: { auth: 'guest' },
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
	parameters: { auth: 'loading' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).queryByRole('link', { name: 'Sign Up' })).toBeNull();
	},
};

/** A signed-in user sees only their name; Dashboard and Log out sit behind it. */
export const SignedIn: Story = {
	parameters: { auth: signedInAs('Hanako') },
	play: async ({ canvasElement }) => {
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
			await expect(authSpies.logout).toHaveBeenCalled();
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
	parameters: { auth: signedInAs('Hanako') },
	play: async ({ canvasElement }) => {
		if (!isDesktop()) return;
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Explore' }));
		await expect(canvas.getByRole('link', { name: /^Articles/ })).toHaveAttribute('href', '/articles');
	},
};

/** A 20-character name, for checking the bar still fits; the name truncates past 12rem. */
export const LongName: Story = { parameters: { auth: signedInAs('Hanako Yamamoto-Ito') } };

/** The account list of whichever layout fits: the bar from 1024px, the navigation drawer below. */
const accountScope = async (canvasElement: HTMLElement) => {
	const scope = isDesktop() ? within(canvasElement) : await openMenuDrawer(canvasElement);

	return within(scope.getByRole('list', { name: 'Account' }));
};

/** Account menu, then Log out: the auth context's `logout` runs once. */
export const LogsOut: Story = {
	parameters: { auth: signedInAs('Hanako') },
	play: async ({ canvasElement }) => {
		const account = await accountScope(canvasElement);
		await userEvent.click(account.getByRole('button', { name: 'Hanako' }));
		await userEvent.click(account.getByRole('button', { name: 'Log out' }));
		await expect(authSpies.logout).toHaveBeenCalledTimes(1);
	},
};

/** The realtime dot sits beside the account name, and only for a signed-in viewer. */
export const SocketConnecting: Story = {
	parameters: { auth: signedInAs('Hanako'), socket: 'connecting' },
	play: async ({ canvasElement }) => {
		const account = await accountScope(canvasElement);
		await expect(account.getByRole('button', { name: 'WebSocket connecting' })).toBeInTheDocument();
	},
};

export const SocketReconnecting: Story = {
	parameters: { auth: signedInAs('Hanako'), socket: 'reconnecting' },
	play: async ({ canvasElement }) => {
		const account = await accountScope(canvasElement);
		await expect(account.getByRole('button', { name: 'WebSocket reconnecting' })).toBeInTheDocument();
	},
};

export const SocketFailed: Story = {
	parameters: { auth: signedInAs('Hanako'), socket: 'failed' },
	play: async ({ canvasElement }) => {
		const account = await accountScope(canvasElement);
		await expect(account.getByRole('button', { name: 'WebSocket failed' })).toBeInTheDocument();
	},
};

/** With no realtime server configured the dot is not rendered, and the account still is. */
export const SocketUnconfigured: Story = {
	parameters: { auth: signedInAs('Hanako'), socket: 'unconfigured' },
	play: async ({ canvasElement }) => {
		const account = await accountScope(canvasElement);
		await expect(account.queryByRole('button', { name: /^WebSocket/ })).toBeNull();
		await expect(account.getByRole('button', { name: 'Hanako' })).toBeInTheDocument();
	},
};

/** At 360px the bar collapses to the brand, search and menu buttons; everything else is in the drawer. */
export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	parameters: { auth: signedInAs('Hanako') },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('link', { name: 'JPLearning' })).toBeVisible();

		const drawer = await openMenuDrawer(canvasElement);
		await expect(drawer.getByRole('heading', { name: 'Explore' })).toBeInTheDocument();
		await expect(drawer.getByRole('list', { name: 'Account' })).toBeInTheDocument();
		await userEvent.click(drawer.getByRole('button', { name: 'Close navigation' }));
		await waitFor(() => expect(canvas.getByRole('button', { name: 'Open navigation' })).toHaveFocus());
	},
};
