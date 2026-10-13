import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { authSpies, currentLocation } from '@/storybook/decorators';
import RegisterPage from './';

/*
 * `/register` on its real route component. The form has its own stories under Features/Auth/Register;
 * these are the page's: the route it sits on and where it sends a signed-in visitor.
 */

const VALID = {
	name: 'hanako_yamada',
	email: 'hanako@example.com',
	password: 'Sup3r-secret!',
	password_confirmation: 'Sup3r-secret!',
};

const meta = {
	title: 'Pages/Register',
	component: RegisterPage,
	// The page is for a signed-out visitor; stories that need another viewer say so.
	parameters: {
		layout: 'fullscreen',
		auth: 'guest',
		router: { path: '/register', initialEntries: ['/register'] },
	},
} satisfies Meta<typeof RegisterPage>;

export default meta;

type Story = StoryObj<typeof meta>;

/** A signed-out visitor sees the form; a valid submit calls `register` with what was typed. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('heading', { level: 1, name: 'Create your account' })).toBeVisible();

		await userEvent.type(canvas.getByLabelText('Username'), VALID.name);
		await userEvent.type(canvas.getByLabelText('Email'), VALID.email);
		await userEvent.type(canvas.getByLabelText('Password'), VALID.password);
		await userEvent.type(canvas.getByLabelText('Confirm password'), VALID.password_confirmation);
		await userEvent.click(canvas.getByRole('button', { name: 'Sign up' }));

		await waitFor(() => expect(authSpies.register).toHaveBeenCalledWith(VALID));
		await expect(currentLocation()).toBe('/register');
	},
};

/** A signed-in visitor who opens /register lands on the home page, not on the form. */
export const AlreadySignedIn: Story = {
	parameters: { auth: 'member' },
	play: async () => {
		await waitFor(() => expect(currentLocation()).toBe('/'));
	},
};

/** The link back to sign in is on the page, and following it leaves for /login. */
export const LinksToLogin: Story = {
	play: async ({ canvasElement }) => {
		const link = within(canvasElement).getByRole('link', { name: 'Log in' });

		await expect(link).toHaveAttribute('href', '/login');

		await userEvent.click(link);
		await waitFor(() => expect(currentLocation()).toBe('/login'));
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
};
