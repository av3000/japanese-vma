import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import Header from './index';

const authState = vi.hoisted(() => ({
	value: {
		isAuthenticated: false,
		isLoading: false,
		user: null,
		logout: vi.fn(),
	} as any,
}));

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => authState.value,
}));

vi.mock('@/components/features/SocketStatusIndicator', () => ({
	default: () => <span>Socket status</span>,
}));

const renderHeader = () =>
	renderToStaticMarkup(
		<MemoryRouter>
			<Header />
		</MemoryRouter>,
	);

describe('Header', () => {
	it('renders public nav and neutral auth controls while auth is checking', () => {
		authState.value = {
			isAuthenticated: false,
			isLoading: true,
			user: null,
			logout: vi.fn(),
		};

		const html = renderHeader();

		expect(html).toContain('Articles');
		expect(html).toContain('Catalogues');
		expect(html).toContain('Japanese Material');
		expect(html).toContain('aria-label="Checking account status"');
		expect(html).not.toContain('Sign Up');
		expect(html).not.toContain('Log In');
		expect(html).not.toContain('Dashboard');
		expect(html).not.toContain('Logged in as');
		expect(html).not.toContain('Logout');
	});

	it('renders guest auth actions when auth is anonymous', () => {
		authState.value = {
			isAuthenticated: false,
			isLoading: false,
			user: null,
			logout: vi.fn(),
		};

		const html = renderHeader();

		expect(html).toContain('Sign Up');
		expect(html).toContain('Log In');
		expect(html).not.toContain('Logged in as');
		expect(html).not.toContain('Logout');
	});

	it('renders logged-in controls when auth is authenticated', () => {
		authState.value = {
			isAuthenticated: true,
			isLoading: false,
			user: {
				id: 1,
				uuid: 'user-uuid',
				name: 'Alana',
				email: 'alana@example.com',
				roles: [],
				isAdmin: false,
			},
			logout: vi.fn(),
		};

		const html = renderHeader();

		expect(html).toContain('Dashboard');
		expect(html).toContain('Logged in as');
		expect(html).toContain('Alana');
		expect(html).toContain('Logout');
		expect(html).not.toContain('Sign Up');
		expect(html).not.toContain('Log In');
	});
});
