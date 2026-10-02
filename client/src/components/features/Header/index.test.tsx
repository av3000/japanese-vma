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

const signedIn = {
	isAuthenticated: true,
	isLoading: false,
	user: { id: 1, uuid: 'user-uuid', name: 'Alana', email: 'alana@example.com', roles: [], isAdmin: false },
	logout: vi.fn(),
};

describe('Header', () => {
	it.each([
		['checking', { isAuthenticated: false, isLoading: true, user: null, logout: vi.fn() }],
		['a guest', { isAuthenticated: false, isLoading: false, user: null, logout: vi.fn() }],
		['signed in', signedIn],
	])('renders the Explore and Dictionary groups and the search for every auth state (%s)', (_, auth) => {
		authState.value = auth;
		const html = renderHeader();

		expect(html).toContain('>Explore<');
		expect(html).toContain('>Dictionary<');
		expect(html).toContain('href="/kanjis"');
		expect(html).toContain('role="search"');
		expect(html).not.toContain('Japanese Material');
		expect(html).not.toContain('new-nav-group');
		expect(html).not.toContain('/newarticle');
		expect(html).not.toContain('/catalogues/new');
		expect(html).not.toContain('/newpost');
	});

	it('renders neutral account controls while auth is checking', () => {
		authState.value = { isAuthenticated: false, isLoading: true, user: null, logout: vi.fn() };
		const html = renderHeader();

		expect(html).toContain('aria-label="Checking account status"');
		expect(html).not.toContain('Sign Up');
		expect(html).not.toContain('Log In');
		expect(html).not.toContain('Dashboard');
		expect(html).not.toContain('Log out');
	});

	it('renders Log In then Sign Up for a guest', () => {
		authState.value = { isAuthenticated: false, isLoading: false, user: null, logout: vi.fn() };
		const html = renderHeader();

		expect(html).toMatch(/<a[^>]*href="\/login"[^>]*>Log In<\/a>/);
		expect(html).toMatch(/<a[^>]*href="\/register"[^>]*>Sign Up<\/a>/);
		expect(html.indexOf('Log In')).toBeLessThan(html.indexOf('Sign Up'));
		expect(html).not.toContain('Dashboard');
		expect(html).not.toContain('Log out');
	});

	it('collapses a signed-in user into their name, with Dashboard and Log out behind it', () => {
		authState.value = signedIn;
		const html = renderHeader();

		expect(html).toContain('Alana');
		expect(html).toContain('Dashboard');
		expect(html).toContain('Log out');
		expect(html).toContain('Socket status');
		expect(html).toContain('aria-controls="account-nav-group"');
		expect(html).not.toContain('Logged in as');
		expect(html).not.toContain('Logout');
		expect(html).not.toContain('Sign Up');
		expect(html).not.toContain('Log In');
	});
});
