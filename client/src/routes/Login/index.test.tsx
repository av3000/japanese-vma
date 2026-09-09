// @vitest-environment jsdom
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { authLogin } from '@/api/generated/auth/auth';
import { AuthProvider } from '@/providers/contexts/auth-provider';
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

const renderLogin = () =>
	renderWithAct(
		<MemoryRouter>
			<AuthProvider>
				<LoginPage />
			</AuthProvider>
		</MemoryRouter>,
	);

const submit = (container: HTMLElement, credentials: { email: string; password: string }) => {
	const form = requireElement<HTMLFormElement>(container, 'form');
	requireElement<HTMLInputElement>(form, '#email').value = credentials.email;
	requireElement<HTMLInputElement>(form, '#password').value = credentials.password;
	form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
};

describe('Login page', () => {
	beforeEach(() => {
		navigate.mockClear();
		vi.mocked(authLogin).mockReset();
		localStorage.clear();
		vi.spyOn(console, 'error').mockImplementation(() => {});
	});

	afterEach(() => {
		vi.restoreAllMocks();
	});

	it('renders the login form', async () => {
		const view = await renderLogin();

		expect(view.container.textContent).toContain('Welcome back!');
		expect(view.container.querySelector('button[type="submit"]')?.textContent).toBe('Log in');

		await view.unmount();
	});

	it('signs in through the generated client and lands on the home route', async () => {
		vi.mocked(authLogin).mockResolvedValue({
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
		});

		const view = await renderLogin();
		await view.flush(() => submit(view.container, { email: 'sora@example.com', password: 'secret' }));

		expect(authLogin).toHaveBeenCalledWith({ email: 'sora@example.com', password: 'secret' });
		expect(localStorage.getItem('token')).toBe('fresh-token');
		expect(navigate).toHaveBeenCalledWith('/');

		await view.unmount();
	});

	it('shows the server message on invalid credentials and stays put', async () => {
		vi.mocked(authLogin).mockRejectedValue(
			Object.assign(new Error('Request failed'), {
				response: { status: 401, data: { message: 'Invalid email or password' } },
			}),
		);

		const view = await renderLogin();
		await view.flush(() => submit(view.container, { email: 'sora@example.com', password: 'wrong' }));

		expect(view.container.textContent).toContain('Invalid email or password');
		expect(localStorage.getItem('token')).toBeNull();
		expect(navigate).not.toHaveBeenCalled();

		await view.unmount();
	});
});
