import { Route, Routes, useLocation } from 'react-router-dom';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, waitFor, within } from 'storybook/test';
import { currentLocation } from '@/storybook/decorators';
import PrivateRoute from './PrivateRoute';

/** What the login route would show: where the guest was sent from, read off the router state. */
const LoginProbe = () => {
	const { state } = useLocation() as { state: { from?: { pathname: string } } | null };

	return <p>Login page, sent from {state?.from?.pathname ?? 'nowhere'}</p>;
};

/** The guard as `routes.tsx` mounts it: a layout route around the private pages, plus the login route. */
const Guarded = () => (
	<Routes>
		<Route element={<PrivateRoute />}>
			<Route path="/dashboard" element={<p>Dashboard content</p>} />
		</Route>
		<Route path="/login" element={<LoginProbe />} />
	</Routes>
);

const meta = {
	title: 'Pages/PrivateRoute',
	component: PrivateRoute,
	parameters: { layout: 'fullscreen', router: { initialEntries: ['/dashboard'] } },
	render: () => <Guarded />,
} satisfies Meta<typeof PrivateRoute>;

export default meta;

type Story = StoryObj<typeof meta>;

/** A guest is replaced onto `/login`, and the page they asked for travels in `state.from`. */
export const Guest: Story = {
	parameters: { auth: 'guest' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('Login page, sent from /dashboard')).toBeVisible();
		await expect(currentLocation()).toBe('/login');
		await expect(canvas.queryByText('Dashboard content')).toBeNull();
	},
};

/** While the session is being checked the guard shows a placeholder, and neither redirects nor renders the page. */
export const Loading: Story = {
	parameters: { auth: 'loading' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('status', { name: 'Checking session' })).toBeVisible();
		await expect(canvas.queryByText('Dashboard content')).toBeNull();
		await waitFor(() => expect(currentLocation()).toBe('/dashboard'));
	},
};

/** A signed-in member passes through: the guarded page renders where the guard sits. */
export const Member: Story = {
	parameters: { auth: 'member' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Dashboard content')).toBeVisible();
		await expect(currentLocation()).toBe('/dashboard');
	},
};
