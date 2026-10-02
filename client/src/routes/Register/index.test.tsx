// @vitest-environment jsdom
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { authRegister } from '@/api/generated/auth/auth';
import type { AuthRegister201 } from '@/api/generated/model/authRegister201';
import { PASSWORD_RULE_MESSAGES } from '@/components/features/auth/Register/registerSchema';
import { AuthProvider } from '@/providers/contexts/auth-provider';
import { typeInto } from '@/test/formEvents';
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

const registered: AuthRegister201 = {
	success: true,
	data: {
		id: 11,
		uuid: 'new-user-uuid',
		name: 'sora',
		email: 'sora@example.com',
		roles: [],
		is_admin: false,
		created_at: '2026-01-02T03:04:05+00:00',
		access_token: 'new-token',
		token_type: 'Bearer',
	},
};

// Shape copied from the local API (app/Exceptions/Handler.php, ValidationException branch).
const takenNameAndEmail = Object.assign(new Error('Request failed with status code 422'), {
	response: {
		status: 422,
		data: {
			type: 'https://tools.ietf.org/html/rfc4918#section-11.2',
			title: 'Validation failed',
			status: 422,
			detail: 'One or more validation errors occurred',
			instance: 'api/v1/register',
			timestamp: '2026-10-02T05:53:02+00:00',
			errors: {
				name: ['The name has already been taken.'],
				email: ['The email has already been taken.'],
			},
		},
	},
});

const details = {
	name: 'sora',
	email: 'sora@example.com',
	password: 'Str0ng!Passw0rd',
	password_confirmation: 'Str0ng!Passw0rd',
};

const renderRegister = (state?: unknown) =>
	renderWithAct(
		<MemoryRouter initialEntries={[{ pathname: '/register', state }]}>
			<AuthProvider>
				<RegisterPage />
			</AuthProvider>
		</MemoryRouter>,
	);

type View = Awaited<ReturnType<typeof renderRegister>>;

const fillAndSubmit = async (view: View, values: typeof details) => {
	for (const [field, value] of Object.entries(values)) {
		typeInto(requireElement<HTMLInputElement>(view.container, `#${field}`), value);
	}
	await view.flush(() => requireElement<HTMLFormElement>(view.container, 'form').requestSubmit());
};

const errorTexts = (view: View, field: string) =>
	Array.from(view.container.querySelectorAll(`#${field}-error p`)).map((p) => p.textContent);

describe('Register page', () => {
	beforeEach(() => {
		navigate.mockClear();
		vi.mocked(authRegister).mockReset();
		localStorage.clear();
	});

	afterEach(() => {
		vi.restoreAllMocks();
	});

	it('renders one h1, hints for the rules, and the link to Login', async () => {
		const view = await renderRegister();

		expect(view.container.querySelectorAll('h1')).toHaveLength(1);
		expect(view.container.querySelector('h1')?.textContent).toBe('Create your account');
		expect(view.container.querySelector('#password-hint')?.textContent).toContain('At least 8 characters');
		expect(view.container.querySelector('#name')?.getAttribute('autocomplete')).toBe('nickname');
		expect(view.container.querySelector('button[type="submit"]')?.textContent).toBe('Sign up');
		expect(view.container.querySelector('a[href="/login"]')?.textContent).toBe('Log in');

		await view.unmount();
	});

	it('registers through the generated client and lands on the home route', async () => {
		vi.mocked(authRegister).mockResolvedValue(registered);

		const view = await renderRegister();
		await fillAndSubmit(view, details);

		expect(authRegister).toHaveBeenCalledWith(details);
		expect(localStorage.getItem('token')).toBe('new-token');
		expect(navigate).toHaveBeenCalledTimes(1);
		expect(navigate).toHaveBeenCalledWith('/', { replace: true });

		await view.unmount();
	});

	it('returns to the page the user was bounced from before the detour through Login', async () => {
		vi.mocked(authRegister).mockResolvedValue(registered);

		const view = await renderRegister({ from: { pathname: '/dashboard', search: '', hash: '' } });
		await fillAndSubmit(view, details);

		expect(navigate).toHaveBeenCalledWith('/dashboard', { replace: true });

		await view.unmount();
	});

	it('shows a taken name and a taken email under their own fields, never the axios text', async () => {
		vi.mocked(authRegister).mockRejectedValue(takenNameAndEmail);

		const view = await renderRegister();
		await fillAndSubmit(view, details);

		expect(errorTexts(view, 'name')).toEqual(['The name has already been taken.']);
		expect(errorTexts(view, 'email')).toEqual(['The email has already been taken.']);
		expect(requireElement<HTMLInputElement>(view.container, '#email').getAttribute('aria-describedby')).toBe(
			'email-error',
		);
		expect(view.container.querySelector('[role="alert"]')).toBeNull();
		expect(view.container.textContent).not.toContain('Request failed');
		expect(localStorage.getItem('token')).toBeNull();
		expect(navigate).not.toHaveBeenCalled();

		await view.unmount();
	});

	it('lists every broken password rule and blocks the submit', async () => {
		const view = await renderRegister();
		await fillAndSubmit(view, { ...details, password: 'weak', password_confirmation: 'weak' });

		expect(authRegister).not.toHaveBeenCalled();
		expect(errorTexts(view, 'password')).toEqual([
			PASSWORD_RULE_MESSAGES.length,
			PASSWORD_RULE_MESSAGES.mixedCase,
			PASSWORD_RULE_MESSAGES.number,
			PASSWORD_RULE_MESSAGES.symbol,
		]);
		expect(requireElement<HTMLInputElement>(view.container, '#password').getAttribute('aria-describedby')).toBe(
			'password-hint password-error',
		);

		await view.unmount();
	});

	it('catches a confirmation mismatch before calling the API', async () => {
		const view = await renderRegister();
		await fillAndSubmit(view, { ...details, password_confirmation: 'Str0ng!Passw0rd-typo' });

		expect(authRegister).not.toHaveBeenCalled();
		expect(errorTexts(view, 'password_confirmation')).toEqual(['The passwords do not match.']);

		await view.unmount();
	});

	it('says the server is unreachable when the request never gets a response', async () => {
		vi.mocked(authRegister).mockRejectedValue(new Error('Network Error'));

		const view = await renderRegister();
		await fillAndSubmit(view, details);

		expect(requireElement<HTMLElement>(view.container, '[role="alert"]').textContent).toContain(
			"We couldn't reach the server",
		);

		await view.unmount();
	});
});
