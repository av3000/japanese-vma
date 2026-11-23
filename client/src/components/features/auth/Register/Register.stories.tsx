import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import { authSpies } from '@/storybook/decorators';
import { validationFailure } from '../fixtures';
import Register from './';
import { PASSWORD_RULE_MESSAGES } from './registerSchema';

const VALID = {
	name: 'hanako_yamada',
	email: 'hanako@example.com',
	password: 'Sup3r-secret!',
	password_confirmation: 'Sup3r-secret!',
};

const fillIn = async (canvasElement: HTMLElement, values: typeof VALID) => {
	const canvas = within(canvasElement);
	await userEvent.type(canvas.getByLabelText('Username'), values.name);
	await userEvent.type(canvas.getByLabelText('Email'), values.email);
	await userEvent.type(canvas.getByLabelText('Password'), values.password);
	await userEvent.type(canvas.getByLabelText('Confirm password'), values.password_confirmation);
	await userEvent.click(canvas.getByRole('button', { name: 'Sign up' }));
};

const meta = {
	title: 'Features/Auth/Register',
	component: Register,
	// The page is for a signed-out visitor.
	parameters: { layout: 'fullscreen', auth: 'guest' },
} satisfies Meta<typeof Register>;

export default meta;

type Story = StoryObj<typeof meta>;

/** A valid form calls `register` with exactly what was typed. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 1, name: 'Create your account' })).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Log in' })).toHaveAttribute('href', '/login');

		await fillIn(canvasElement, VALID);

		await waitFor(() => expect(authSpies.register).toHaveBeenCalledWith(VALID));
		await expect(authSpies.register).toHaveBeenCalledTimes(1);
	},
};

/** The rules the browser can check are reported all at once, per field, and `register` is not called. */
export const ValidationErrors: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await fillIn(canvasElement, {
			name: 'hanako yamada!',
			email: 'not-an-email',
			password: 'weak',
			password_confirmation: 'different',
		});

		await waitFor(() => expect(canvas.getByLabelText('Username')).toHaveFocus());
		await expect(canvas.getByLabelText('Username')).toHaveAccessibleDescription(
			/Username can only contain letters, numbers, underscores, and hyphens\./,
		);
		await expect(canvas.getByLabelText('Email')).toHaveAccessibleDescription(/Enter a valid email address\./);

		const password = canvas.getByLabelText('Password');
		for (const message of Object.values(PASSWORD_RULE_MESSAGES)) {
			await expect(password).toHaveAccessibleDescription(expect.stringContaining(message));
		}

		await expect(canvas.getByLabelText('Confirm password')).toHaveAccessibleDescription(
			/The passwords do not match\./,
		);
		await expect(authSpies.register).not.toHaveBeenCalled();
	},
};

/** An empty submit asks for each value and focuses the first field. */
export const EmptySubmit: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Sign up' }));

		await waitFor(() => expect(canvas.getByLabelText('Username')).toHaveFocus());
		await expect(canvas.getByLabelText('Username')).toHaveAccessibleDescription(/Choose a username\./);
		await expect(canvas.getByLabelText('Email')).toHaveAccessibleDescription('Enter your email.');
		await expect(canvas.getByLabelText('Password')).toHaveAccessibleDescription(/Choose a password\./);
		await expect(canvas.getByLabelText('Confirm password')).toHaveAccessibleDescription('Repeat your password.');
		await expect(authSpies.register).not.toHaveBeenCalled();
	},
};

/** Only the server knows the name and email are taken: its 422 messages land under those fields. */
export const SubmitError: Story = {
	parameters: {
		auth: {
			register: fn(async () => {
				throw validationFailure({
					name: ['The name has already been taken.'],
					email: ['The email has already been taken.'],
					password: ['The given password has appeared in a data leak. Please choose a different password.'],
				});
			}),
		},
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await fillIn(canvasElement, VALID);

		await waitFor(() => expect(canvas.getByLabelText('Username')).toHaveFocus());
		await expect(canvas.getByLabelText('Username')).toHaveAccessibleDescription(/already been taken/);
		await expect(canvas.getByLabelText('Email')).toHaveAccessibleDescription(/already been taken/);
		await expect(canvas.getByLabelText('Password')).toHaveAccessibleDescription(/appeared in a data leak/);
		await expect(canvas.getByRole('button', { name: 'Sign up' })).toBeEnabled();
	},
};

/** While the request is in flight the submit control is disabled, so a second click cannot send it twice. */
export const Submitting: Story = {
	parameters: { auth: { register: fn(() => new Promise<void>(() => {})) } },
	play: async ({ canvasElement }) => {
		await fillIn(canvasElement, VALID);
		await waitFor(() => expect(within(canvasElement).getByRole('button', { name: 'Sign up' })).toBeDisabled());
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
};
