// @vitest-environment jsdom
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { authRegister } from '@/api/generated/auth/auth';
import { AuthProvider } from '@/providers/contexts/auth-provider';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import RegisterPage from './index';

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

const renderRegister = () =>
	renderWithAct(
		<MemoryRouter>
			<AuthProvider>
				<RegisterPage />
			</AuthProvider>
		</MemoryRouter>,
	);

const submit = (
	container: HTMLElement,
	details: { name: string; email: string; password: string; password_confirmation: string },
) => {
	const form = requireElement<HTMLFormElement>(container, 'form');
	Object.entries(details).forEach(([field, value]) => {
		requireElement<HTMLInputElement>(form, `#${field}`).value = value;
	});
	form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
};

const details = {
	name: 'Sora',
	email: 'sora@example.com',
	password: 'secret-password',
	password_confirmation: 'secret-password',
};

describe('Register page', () => {
	beforeEach(() => {
		navigate.mockClear();
		vi.mocked(authRegister).mockReset();
		localStorage.clear();
		vi.spyOn(console, 'error').mockImplementation(() => {});
	});

	afterEach(() => {
		vi.restoreAllMocks();
	});

	it('renders the register form', async () => {
		const view = await renderRegister();

		expect(view.container.textContent).toContain('Join us!');
		expect(view.container.querySelector('button[type="submit"]')?.textContent).toBe('Sign up');

		await view.unmount();
	});

	it('registers through the generated client and lands on the home route', async () => {
		vi.mocked(authRegister).mockResolvedValue({
			success: true,
			data: {
				id: 11,
				uuid: 'new-user-uuid',
				name: 'Sora',
				email: 'sora@example.com',
				roles: [],
				is_admin: false,
				created_at: '2026-01-02T03:04:05+00:00',
				access_token: 'new-token',
				token_type: 'Bearer',
			},
		});

		const view = await renderRegister();
		await view.flush(() => submit(view.container, details));

		expect(authRegister).toHaveBeenCalledWith(details);
		expect(localStorage.getItem('token')).toBe('new-token');
		expect(navigate).toHaveBeenCalledWith('/');

		await view.unmount();
	});

	it('surfaces a validation failure and stays put', async () => {
		vi.mocked(authRegister).mockRejectedValue(
			Object.assign(new Error('Request failed'), {
				response: { status: 422, data: { message: 'The email has already been taken.' } },
			}),
		);

		const view = await renderRegister();
		await view.flush(() => submit(view.container, details));

		expect(view.container.textContent).toContain('The email has already been taken.');
		expect(localStorage.getItem('token')).toBeNull();
		expect(navigate).not.toHaveBeenCalled();

		await view.unmount();
	});
});
