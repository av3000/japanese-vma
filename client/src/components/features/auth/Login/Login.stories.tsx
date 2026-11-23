import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import { AUTH_ERROR_MESSAGES } from '@/api/auth/authError';
import { authSpies, currentLocation } from '@/storybook/decorators';
import { validationFailure } from '../fixtures';
import Login from './';

const fillIn = async (canvasElement: HTMLElement, email: string, password: string) => {
	const canvas = within(canvasElement);
	await userEvent.type(canvas.getByLabelText('Email'), email);
	await userEvent.type(canvas.getByLabelText('Password'), password);
	await userEvent.click(canvas.getByRole('button', { name: 'Log in' }));
};

const meta = {
	title: 'Features/Auth/Login',
	component: Login,
	// The page is for a signed-out visitor; stories that need another viewer say so.
	parameters: { layout: 'fullscreen', auth: 'guest' },
} satisfies Meta<typeof Login>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Submitting calls `login` with exactly what was typed. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 1, name: 'Welcome back' })).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Create an account' })).toHaveAttribute('href', '/register');

		await fillIn(canvasElement, 'hanako@example.com', 'correct horse');

		await waitFor(() =>
			expect(authSpies.login).toHaveBeenCalledWith({ email: 'hanako@example.com', password: 'correct horse' }),
		);
		await expect(authSpies.login).toHaveBeenCalledTimes(1);
		await expect(authSpies.clearSessionExpired).toHaveBeenCalled();
	},
};

/** An empty submit stops in the browser: each field says what is missing and `login` is not called. */
export const EmptySubmit: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Log in' }));

		await waitFor(() => expect(canvas.getByLabelText('Email')).toHaveFocus());
		await expect(canvas.getByLabelText('Email')).toHaveAccessibleDescription('Enter your email.');
		await expect(canvas.getByLabelText('Password')).toHaveAccessibleDescription('Enter your password.');
		await expect(authSpies.login).not.toHaveBeenCalled();
	},
};

/** `PrivateRoute` bounced a signed-out visitor here after their session ended. */
export const SessionExpired: Story = {
	parameters: { auth: { user: null, isAuthenticated: false, sessionExpired: true } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Your session expired. Log in again to continue.')).toBeVisible();
	},
};

/** The server rejects the credentials with a 422: its messages sit under the fields they name. */
export const SubmitError: Story = {
	parameters: {
		auth: {
			login: fn(async () => {
				throw validationFailure({
					email: ['These credentials do not match our records.'],
					password: ['The password is incorrect.'],
				});
			}),
		},
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await fillIn(canvasElement, 'hanako@example.com', 'wrong password');

		await waitFor(() => expect(canvas.getByLabelText('Email')).toHaveFocus());
		await expect(canvas.getByLabelText('Email')).toHaveAccessibleDescription(
			'These credentials do not match our records.',
		);
		await expect(canvas.getByLabelText('Password')).toHaveAccessibleDescription('The password is incorrect.');
		await expect(canvas.getByRole('button', { name: 'Log in' })).toBeEnabled();
	},
};

/** A request that never reached the server shows the general alert, not a field error. */
export const Unreachable: Story = {
	parameters: {
		auth: {
			login: fn(async () => {
				throw new Error('Network Error');
			}),
		},
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await fillIn(canvasElement, 'hanako@example.com', 'correct horse');
		await expect(await canvas.findByRole('alert')).toHaveTextContent(AUTH_ERROR_MESSAGES.unreachable);
	},
};

/** While the request is in flight the submit control is disabled, so a second click cannot send it twice. */
export const Submitting: Story = {
	parameters: { auth: { login: fn(() => new Promise<void>(() => {})) } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await fillIn(canvasElement, 'hanako@example.com', 'correct horse');
		await waitFor(() => expect(canvas.getByRole('button', { name: 'Log in' })).toBeDisabled());
	},
};

/** Already signed in on arrival: the page follows `location.state.from` instead of showing the form. */
export const FollowsReturnPath: Story = {
	parameters: {
		auth: 'member',
		router: {
			// With a route, the page unmounts once it navigates away, as in the app; without one it would redirect again.
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

/** Without a return path a signed-in visitor lands on the home page. */
export const FollowsHomeByDefault: Story = {
	parameters: { auth: 'member', router: { initialEntries: ['/login'], path: '/login' } },
	play: async () => {
		await waitFor(() => expect(currentLocation()).toBe('/'));
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
};
