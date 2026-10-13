import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { authSpies, currentLocation } from '@/storybook/decorators';
import LoginPage from './';

/*
 * `/login` on its real route component. The form has its own stories under Features/Auth/Login;
 * these are the page's: the route it sits on, where it sends a signed-in visitor, and the state
 * `PrivateRoute` leaves in `location.state`.
 */

const meta = {
	title: 'Pages/Login',
	component: LoginPage,
	// The page is for a signed-out visitor; stories that need another viewer say so.
	parameters: { layout: 'fullscreen', auth: 'guest', router: { path: '/login', initialEntries: ['/login'] } },
} satisfies Meta<typeof LoginPage>;

export default meta;

type Story = StoryObj<typeof meta>;

/** A signed-out visitor sees the form; submitting calls `login` with what was typed. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('heading', { level: 1, name: 'Welcome back' })).toBeVisible();

		await userEvent.type(canvas.getByLabelText('Email'), 'hanako@example.com');
		await userEvent.type(canvas.getByLabelText('Password'), 'correct horse');
		await userEvent.click(canvas.getByRole('button', { name: 'Log in' }));

		await waitFor(() =>
			expect(authSpies.login).toHaveBeenCalledWith({ email: 'hanako@example.com', password: 'correct horse' }),
		);
		await expect(currentLocation()).toBe('/login');
	},
};

/** `PrivateRoute` bounced a signed-out visitor here after their session ended. */
export const SessionExpired: Story = {
	parameters: { auth: { user: null, isAuthenticated: false, sessionExpired: true } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Your session expired. Log in again to continue.')).toBeVisible();
	},
};

/**
 * `PrivateRoute` sent the visitor here from `/catalogues?page=2`. Signing in flips `isAuthenticated`,
 * and the page then follows `location.state.from`; here the viewer is already signed in, which takes
 * the same path, and the page unmounts as it does in the app.
 */
export const ReturnPath: Story = {
	parameters: {
		auth: 'member',
		router: {
			path: '/login',
			initialEntries: [
				{ pathname: '/login', state: { from: { pathname: '/catalogues', search: '?page=2', hash: '' } } },
			],
		},
	},
	play: async () => {
		await waitFor(() => expect(currentLocation()).toBe('/catalogues?page=2'));
	},
};

/** A signed-in visitor who opens /login lands on the home page, not on the form. */
export const AlreadySignedIn: Story = {
	parameters: { auth: 'member' },
	play: async () => {
		await waitFor(() => expect(currentLocation()).toBe('/'));
	},
};

/** A return path that points back at an auth page is refused, so a detour cannot loop. */
export const ReturnPathIsNotAnAuthPage: Story = {
	parameters: {
		auth: 'member',
		router: {
			path: '/login',
			initialEntries: [{ pathname: '/login', state: { from: { pathname: '/register', search: '', hash: '' } } }],
		},
	},
	play: async () => {
		await waitFor(() => expect(currentLocation()).toBe('/'));
	},
};

/** The link to sign up is on the page, and following it leaves for /register. */
export const LinksToRegister: Story = {
	play: async ({ canvasElement }) => {
		const link = within(canvasElement).getByRole('link', { name: 'Create an account' });

		await expect(link).toHaveAttribute('href', '/register');

		await userEvent.click(link);
		await waitFor(() => expect(currentLocation()).toBe('/register'));
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
};
