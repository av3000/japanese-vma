// @vitest-environment jsdom
import { MemoryRouter } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import Header from '@/components/features/Header';
import Login from '@/components/features/auth/Login';
import { useTranslation } from '@/hooks/useTranslation';
import { TranslationProvider } from '@/providers/contexts/translation-provider';
import { renderWithAct } from '@/test/renderWithAct';

/**
 * AC-2 and AC-3 of #666, end to end through the real provider, the real generated hooks and the real
 * components: only the HTTP seam and the signed-out viewer are stubbed.
 */

const customInstance = vi.hoisted(() => vi.fn());

vi.mock('@/services/orval-mutator', () => ({ customInstance }));

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({
		user: null,
		isAuthenticated: false,
		isLoading: false,
		sessionExpired: false,
		login: vi.fn(),
		logout: vi.fn(),
		clearSessionExpired: vi.fn(),
	}),
}));

vi.mock('@/components/features/SocketStatusIndicator', () => ({ default: () => null }));

vi.mock('@/api/search/useSearchCounts', () => ({ useSearchCounts: () => ({ counts: null }) }));

/** What the translator saved in Filament for `da`: two of the migrated keys, nothing else. */
let danish: Record<string, string>;

const serve = async (config: { url?: string }) => {
	if (config.url === '/translations/locales') {
		return {
			items: [
				{ code: 'en', name: 'English', is_fallback: true },
				{ code: 'da', name: 'Dansk', is_fallback: false },
			],
		};
	}

	return { locale: 'da', fallback_locale: 'en', messages: danish };
};

let picker: ReturnType<typeof useTranslation>;

const Picker = () => {
	picker = useTranslation();

	return null;
};

const mount = () =>
	renderWithAct(
		// The app's own query defaults: fresh for five minutes, which is what a stale bundle would hide behind.
		<QueryClientProvider
			client={new QueryClient({ defaultOptions: { queries: { retry: false, staleTime: 1000 * 60 * 5 } } })}
		>
			<TranslationProvider>
				<MemoryRouter>
					<Picker />
					<Header />
					<Login />
				</MemoryRouter>
			</TranslationProvider>
		</QueryClientProvider>,
	);

const settle = async (view: Awaited<ReturnType<typeof mount>>) => {
	for (let turn = 0; turn < 5; turn += 1) {
		await view.flush(() => new Promise((resolve) => setTimeout(resolve, 0)));
	}
};

/** The Login form's own submit button: the header search drawer has one too. */
const loginSubmit = (view: Awaited<ReturnType<typeof mount>>) =>
	view.container.querySelector('form[novalidate] button[type="submit"]')?.textContent;

const text = (view: Awaited<ReturnType<typeof mount>>) => view.container.textContent ?? '';

describe('the migrated surfaces under a language switch', () => {
	beforeEach(() => {
		customInstance.mockReset();
		customInstance.mockImplementation(serve);
		danish = { 'account.log_in': 'Log ind', 'auth.login.submit': 'Log på' };
		window.localStorage.clear();
		vi.spyOn(window.navigator, 'languages', 'get').mockReturnValue(['en-US']);
	});

	afterEach(() => {
		vi.restoreAllMocks();
		document.body.innerHTML = '';
	});

	it('shows the two translated keys in Danish and every other migrated label in English (AC-2)', async () => {
		const view = await mount();
		await settle(view);

		expect(text(view)).toContain('Log In');
		expect(text(view)).toContain('Sign Up');
		expect(loginSubmit(view)).toBe('Log in');

		await view.flush(() => picker.setLocale('da'));
		await settle(view);

		const labels = Array.from(view.container.querySelectorAll('a, button, h1, label'), (node) => node.textContent);

		// Translated in Filament: shown in Danish, in the header and the mobile drawer alike.
		expect(labels.filter((label) => label === 'Log ind')).toHaveLength(2);
		expect(loginSubmit(view)).toBe('Log på');
		expect(labels).not.toContain('Log In');

		// Not translated: English, never blank and never a key.
		expect(labels).toContain('Sign Up');
		expect(labels).toContain('Welcome back');
		expect(labels).toContain('Email');
		expect(labels).toContain('Password');
		expect(text(view)).toContain('New here?');
		expect(text(view)).not.toMatch(/account\.|auth\./);

		await view.unmount();
	});

	it('shows a value the translator changed when the learner switches language again (AC-3)', async () => {
		const view = await mount();
		await settle(view);
		await view.flush(() => picker.setLocale('da'));
		await settle(view);

		expect(loginSubmit(view)).toBe('Log på');

		danish = { ...danish, 'auth.login.submit': 'Log ind nu' };

		await view.flush(() => picker.setLocale('en'));
		await settle(view);
		expect(loginSubmit(view)).toBe('Log in');

		await view.flush(() => picker.setLocale('da'));
		await settle(view);

		expect(loginSubmit(view)).toBe('Log ind nu');

		await view.unmount();
	});

	it('shows a changed value after a reload, with nothing carried over (AC-3)', async () => {
		const first = await mount();
		await settle(first);
		await first.flush(() => picker.setLocale('da'));
		await settle(first);
		await first.unmount();

		danish = { ...danish, 'account.log_in': 'Log på her' };

		// A reload: a new page, so a new query client, and the stored choice brings Danish back.
		const second = await mount();
		await settle(second);

		expect(text(second)).toContain('Log på her');
		expect(text(second)).not.toContain('Log ind');

		await second.unmount();
	});
});
