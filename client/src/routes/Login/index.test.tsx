// @vitest-environment jsdom
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { AUTH_FAILURE_MESSAGES } from '@/api/auth/authFailure';
import { authLogin } from '@/api/generated/auth/auth';
import type { AuthLogin200 } from '@/api/generated/model/authLogin200';
import { AuthProvider } from '@/providers/contexts/auth-provider';
import { typeInto } from '@/test/formEvents';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import LoginPage from './index';

const navigate = vi.fn();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return { ...actual, useNavigate: () => navigate };
});

vi.mock('@/api/generated/auth/auth', () => ({
	authLogin: vi.fn(),
	authRegister: vi.fn(),
	authMe: vi.fn(),
	authLogout: vi.fn(),
}));

const signedIn: AuthLogin200 = {
	success: true,
	data: {
		id: 7,
		uuid: 'user-uuid',
		name: 'Sora',
		email: 'sora@example.com',
		roles: [],
		is_admin: false,
		created_at: '2026-01-02T03:04:05+00:00',
		access_token: 'fresh-token',
		token_type: 'Bearer',
	},
};

// Copied from the local API: TypedResults::fromError(UserErrors::invalidCredentials()).
const invalidCredentials = Object.assign(new Error('Request failed with status code 401'), {
	response: {
		status: 401,
		data: {
			type: 'https://tools.ietf.org/html/rfc7231#section-6.5.2',
			title: 'Invalid credentials',
			status: 401,
			detail: 'The provided email or password is incorrect',
			instance: 'api/v1/login',
			timestamp: '2026-10-02T06:06:54+00:00',
			errorMessage: 'Invalid email or password',
		},
	},
});

const renderLogin = (state?: unknown) =>
	renderWithAct(
		<MemoryRouter initialEntries={[{ pathname: '/login', state }]}>
			<AuthProvider>
				<LoginPage />
			</AuthProvider>
		</MemoryRouter>,
	);

type View = Awaited<ReturnType<typeof renderLogin>>;

const fillAndSubmit = async (view: View, credentials: { email: string; password: string }) => {
	typeInto(requireElement<HTMLInputElement>(view.container, '#email'), credentials.email);
	typeInto(requireElement<HTMLInputElement>(view.container, '#password'), credentials.password);
	await view.flush(() => requireElement<HTMLFormElement>(view.container, 'form').requestSubmit());
};

describe('Login page', () => {
	beforeEach(() => {
		navigate.mockClear();
		vi.mocked(authLogin).mockReset();
		localStorage.clear();
	});

	afterEach(() => {
		vi.restoreAllMocks();
	});

	it('renders one h1, labelled fields and the link to Register', async () => {
		const view = await renderLogin();

		expect(view.container.querySelectorAll('h1')).toHaveLength(1);
		expect(view.container.querySelector('h1')?.textContent).toBe('Welcome back');
		expect(view.container.querySelector('label[for="email"]')?.textContent).toBe('Email');
		expect(view.container.querySelector('label[for="password"]')?.textContent).toBe('Password');
		expect(view.container.querySelector('button[type="submit"]')?.textContent).toBe('Log in');
		expect(view.container.querySelector('a[href="/register"]')?.textContent).toBe('Create an account');

		await view.unmount();
	});

	it('signs in through the generated client and lands on the home route', async () => {
		vi.mocked(authLogin).mockResolvedValue(signedIn);

		const view = await renderLogin();
		await fillAndSubmit(view, { email: ' sora@example.com ', password: 'secret' });

		expect(authLogin).toHaveBeenCalledWith({ email: 'sora@example.com', password: 'secret' });
		expect(localStorage.getItem('token')).toBe('fresh-token');
		expect(navigate).toHaveBeenCalledTimes(1);
		expect(navigate).toHaveBeenCalledWith('/', { replace: true });

		await view.unmount();
	});

	it('returns to the page PrivateRoute bounced from', async () => {
		vi.mocked(authLogin).mockResolvedValue(signedIn);

		const view = await renderLogin({ from: { pathname: '/dashboard', search: '?tab=articles', hash: '' } });
		await fillAndSubmit(view, { email: 'sora@example.com', password: 'secret' });

		expect(navigate).toHaveBeenCalledWith('/dashboard?tab=articles', { replace: true });

		await view.unmount();
	});

	it('shows a plain sentence on wrong credentials, never the axios text, and stays put', async () => {
		vi.mocked(authLogin).mockRejectedValue(invalidCredentials);

		const view = await renderLogin();
		await fillAndSubmit(view, { email: 'sora@example.com', password: 'wrong' });

		const alert = requireElement<HTMLElement>(view.container, '[role="alert"]');
		expect(alert.textContent).toBe(AUTH_FAILURE_MESSAGES.invalidCredentials);
		expect(view.container.textContent).not.toContain('Request failed');
		expect(localStorage.getItem('token')).toBeNull();
		expect(navigate).not.toHaveBeenCalled();

		await view.unmount();
	});

	it('says the server is unreachable when the request never gets a response', async () => {
		vi.mocked(authLogin).mockRejectedValue(new Error('Network Error'));

		const view = await renderLogin();
		await fillAndSubmit(view, { email: 'sora@example.com', password: 'secret' });

		expect(requireElement<HTMLElement>(view.container, '[role="alert"]').textContent).toBe(
			AUTH_FAILURE_MESSAGES.unreachable,
		);

		await view.unmount();
	});

	it('warns that the session expired, and drops the warning once the user tries again', async () => {
		vi.mocked(authLogin).mockRejectedValue(invalidCredentials);

		const view = await renderLogin();
		await view.flush(() => {
			window.dispatchEvent(new CustomEvent('auth:unauthorized'));
		});

		expect(requireElement<HTMLElement>(view.container, '[role="alert"]').textContent).toBe(
			'Your session expired. Log in again to continue.',
		);

		await fillAndSubmit(view, { email: 'sora@example.com', password: 'wrong' });

		expect(view.container.textContent).not.toContain('Your session expired');
		expect(requireElement<HTMLElement>(view.container, '[role="alert"]').textContent).toBe(
			AUTH_FAILURE_MESSAGES.invalidCredentials,
		);

		await view.unmount();
	});

	it('blocks an empty submit with messages linked to each field', async () => {
		const view = await renderLogin();
		await view.flush(() => requireElement<HTMLFormElement>(view.container, 'form').requestSubmit());

		const email = requireElement<HTMLInputElement>(view.container, '#email');
		expect(authLogin).not.toHaveBeenCalled();
		expect(email.getAttribute('aria-invalid')).toBe('true');
		expect(email.getAttribute('aria-describedby')).toBe('email-error');
		expect(view.container.querySelector('#email-error')?.textContent).toBe('Enter your email.');
		expect(view.container.querySelector('#password-error')?.textContent).toBe('Enter your password.');

		await view.unmount();
	});

	it('rejects a malformed email before calling the API', async () => {
		const view = await renderLogin();
		await fillAndSubmit(view, { email: 'not-an-email', password: 'secret' });

		expect(authLogin).not.toHaveBeenCalled();
		expect(view.container.querySelector('#email-error')?.textContent).toBe('Enter a valid email address.');

		await view.unmount();
	});
});
