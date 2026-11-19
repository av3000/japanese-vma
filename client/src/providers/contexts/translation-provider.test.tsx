// @vitest-environment jsdom
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useTranslation } from '@/hooks/useTranslation';
import { LOCALE_STORAGE_KEY } from '@/i18n/locale';
import { renderWithAct } from '@/test/renderWithAct';
import { TranslationProvider } from './translation-provider';

type Request = { url?: string };

/** The seam the generated hooks call. Mocking it keeps the real Orval hooks, query keys and options under test. */
const customInstance = vi.hoisted(() => vi.fn());

vi.mock('@/services/orval-mutator', () => ({ customInstance }));

const bundles: Record<string, Record<string, string>> = {
	da: { 'account.log_in': 'Log ind' },
	sv: { 'account.log_in': 'Logga in' },
};

const localeList = (...codes: string[]) => ({
	items: codes.map((code) => ({
		code,
		name: { en: 'English', da: 'Dansk', sv: 'Svenska' }[code] ?? code,
		is_fallback: code === 'en',
	})),
});

/** How the stubbed API answers: the active locales, plus per-locale overrides for the bundle request. */
interface Api {
	active: string[];
	/** The locale list request fails with a 500. */
	listFails: boolean;
	/** Locales whose bundle request fails with a 500. */
	failing: Set<string>;
	/** Locales whose bundle request waits until released. */
	held: Map<string, () => void>;
}

let api: Api;

const requested = () => customInstance.mock.calls.map((call) => (call[0] as Request).url);
const bundleRequests = () => requested().filter((url) => url !== '/translations/locales');

const serve = async (config: Request) => {
	if (config.url === '/translations/locales') {
		if (api.listFails) {
			throw Object.assign(new Error('Request failed with status code 500'), { response: { status: 500 } });
		}

		return localeList(...api.active);
	}

	const code = decodeURIComponent((config.url ?? '').replace('/translations/', ''));
	const gate = api.held.get(code);

	if (gate !== undefined) {
		await new Promise<void>((resolve) => api.held.set(code, resolve));
	}

	if (api.failing.has(code)) {
		throw Object.assign(new Error('Request failed with status code 500'), { response: { status: 500 } });
	}

	return { locale: code, fallback_locale: 'en', messages: bundles[code] ?? {} };
};

type Snapshot = ReturnType<typeof useTranslation>;

let latest: Snapshot;

const Probe = () => {
	latest = useTranslation();

	return <span>{latest.t('account.log_in')}</span>;
};

let queryClient: QueryClient;

const mount = () => {
	queryClient = new QueryClient({ defaultOptions: { queries: { retry: false, staleTime: Infinity } } });

	return renderWithAct(
		<QueryClientProvider client={queryClient}>
			<TranslationProvider>
				<Probe />
			</TranslationProvider>
		</QueryClientProvider>,
	);
};

/** Lets queued promises, query updates and effects run. */
const settle = async (view: Awaited<ReturnType<typeof mount>>) => {
	// A few turns: each answer re-renders the provider, which can start the next request.
	for (let turn = 0; turn < 5; turn += 1) {
		await view.flush(() => new Promise((resolve) => setTimeout(resolve, 0)));
	}
};

const browserLanguages = (...languages: string[]) => {
	vi.spyOn(window.navigator, 'languages', 'get').mockReturnValue(languages);
};

describe('TranslationProvider', () => {
	beforeEach(() => {
		api = { active: ['en', 'da'], listFails: false, failing: new Set(), held: new Map() };
		customInstance.mockReset();
		customInstance.mockImplementation(serve);
		window.localStorage.clear();
		document.documentElement.lang = '';
		browserLanguages('en-US');
	});

	afterEach(() => {
		vi.restoreAllMocks();
		document.body.innerHTML = '';
	});

	describe('choosing the first language', () => {
		it('requests the browser base language when it is active', async () => {
			browserLanguages('da-DK');

			const view = await mount();
			await settle(view);

			expect(bundleRequests()).toEqual(['/translations/da']);
			expect(latest.locale).toBe('da');
			expect(view.container.textContent).toBe('Log ind');
			await view.unmount();
		});

		it('stays on English when the browser language is not active', async () => {
			browserLanguages('da-DK');
			api.active = ['en'];

			const view = await mount();
			await settle(view);

			expect(bundleRequests()).toEqual([]);
			expect(latest.locale).toBe('en');
			expect(view.container.textContent).toBe('Log In');
			expect(document.documentElement.lang).toBe('en');
			await view.unmount();
		});

		it('does not ask for a language before the list says it is active', async () => {
			window.localStorage.setItem(LOCALE_STORAGE_KEY, 'da');
			let releaseList: () => void = () => undefined;
			customInstance.mockImplementation(async (config: Request) => {
				if (config.url === '/translations/locales') {
					await new Promise<void>((resolve) => {
						releaseList = resolve;
					});
				}

				return serve(config);
			});

			const view = await mount();
			await settle(view);

			expect(requested()).toEqual(['/translations/locales']);

			await view.flush(() => releaseList());
			await settle(view);

			expect(bundleRequests()).toEqual(['/translations/da']);
			await view.unmount();
		});

		it('prefers the stored choice to the browser language', async () => {
			browserLanguages('da-DK');
			api.active = ['en', 'da', 'sv'];
			window.localStorage.setItem(LOCALE_STORAGE_KEY, 'sv');

			const view = await mount();
			await settle(view);

			expect(bundleRequests()).toEqual(['/translations/sv']);
			expect(view.container.textContent).toBe('Logga in');
			await view.unmount();
		});

		it('falls back to English when the stored locale is no longer active', async () => {
			window.localStorage.setItem(LOCALE_STORAGE_KEY, 'sv');

			const view = await mount();
			await settle(view);

			expect(bundleRequests()).toEqual([]);
			expect(latest.locale).toBe('en');
			await view.unmount();
		});
	});

	describe('switching language', () => {
		it('updates the labels, the stored choice and <html lang> without a reload', async () => {
			const view = await mount();
			await settle(view);

			expect(view.container.textContent).toBe('Log In');
			expect(latest.locales.map((option) => option.code)).toEqual(['en', 'da']);
			expect(document.documentElement.lang).toBe('en');

			await view.flush(() => latest.setLocale('da'));
			await settle(view);

			expect(view.container.textContent).toBe('Log ind');
			expect(window.localStorage.getItem(LOCALE_STORAGE_KEY)).toBe('da');
			expect(document.documentElement.lang).toBe('da');
			await view.unmount();
		});

		it('holds the choice across a reload while the locale is active', async () => {
			const first = await mount();
			await settle(first);
			await first.flush(() => latest.setLocale('da'));
			await settle(first);
			await first.unmount();

			const reloaded = await mount();
			await settle(reloaded);

			expect(latest.locale).toBe('da');
			expect(reloaded.container.textContent).toBe('Log ind');
			await reloaded.unmount();
		});

		it('goes back to English from the snapshot, with no request', async () => {
			const view = await mount();
			await settle(view);
			await view.flush(() => latest.setLocale('da'));
			await settle(view);
			customInstance.mockClear();

			await view.flush(() => latest.setLocale('en'));
			await settle(view);

			expect(bundleRequests()).toEqual([]);
			expect(view.container.textContent).toBe('Log In');
			expect(document.documentElement.lang).toBe('en');
			await view.unmount();
		});

		it('ignores a language that is not in the list', async () => {
			const view = await mount();
			await settle(view);

			await view.flush(() => latest.setLocale('xx'));
			await settle(view);

			expect(latest.locale).toBe('en');
			expect(window.localStorage.getItem(LOCALE_STORAGE_KEY)).toBeNull();
			await view.unmount();
		});

		it('still switches when storage throws', async () => {
			vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
				throw new Error('blocked');
			});

			const view = await mount();
			await settle(view);
			await view.flush(() => latest.setLocale('da'));
			await settle(view);

			expect(view.container.textContent).toBe('Log ind');
			await view.unmount();
		});
	});

	describe('while a bundle loads or fails', () => {
		it('keeps the previous labels until the new bundle arrives', async () => {
			api.active = ['en', 'da', 'sv'];
			const view = await mount();
			await settle(view);
			await view.flush(() => latest.setLocale('da'));
			await settle(view);

			api.held.set('sv', () => undefined);
			await view.flush(() => latest.setLocale('sv'));
			await settle(view);

			// Swedish is on its way: Danish is still what is shown, and the selector already says Swedish.
			expect(view.container.textContent).toBe('Log ind');
			expect(latest.locale).toBe('sv');
			expect(latest.displayedLocale).toBe('da');
			expect(document.documentElement.lang).toBe('da');
			expect(latest.error).toBeNull();

			await view.flush(() => api.held.get('sv')?.());
			await settle(view);

			expect(view.container.textContent).toBe('Logga in');
			expect(latest.displayedLocale).toBe('sv');
			expect(document.documentElement.lang).toBe('sv');
			await view.unmount();
		});

		it('keeps English on screen while the first other language loads', async () => {
			api.held.set('da', () => undefined);
			const view = await mount();
			await settle(view);
			await view.flush(() => latest.setLocale('da'));
			await settle(view);

			expect(view.container.textContent).toBe('Log In');
			expect(latest.displayedLocale).toBe('en');
			await view.unmount();
		});

		it('keeps the previous labels and reports a non-blocking error when the request fails', async () => {
			api.active = ['en', 'da', 'sv'];
			const view = await mount();
			await settle(view);
			await view.flush(() => latest.setLocale('da'));
			await settle(view);

			api.failing.add('sv');
			await view.flush(() => latest.setLocale('sv'));
			await settle(view);

			expect(view.container.textContent).toBe('Log ind');
			expect(latest.displayedLocale).toBe('da');
			expect(document.documentElement.lang).toBe('da');
			expect(latest.error).toEqual({ locale: 'sv' });

			// Picking another language clears it.
			await view.flush(() => latest.setLocale('en'));
			await settle(view);

			expect(latest.error).toBeNull();
			expect(view.container.textContent).toBe('Log In');
			await view.unmount();
		});

		it('shows English and an error when the stored language fails to load', async () => {
			window.localStorage.setItem(LOCALE_STORAGE_KEY, 'da');
			api.failing.add('da');

			const view = await mount();
			await settle(view);

			expect(view.container.textContent).toBe('Log In');
			expect(latest.error).toEqual({ locale: 'da' });
			await view.unmount();
		});

		it('shows English with no selector when the locale list fails', async () => {
			window.localStorage.setItem(LOCALE_STORAGE_KEY, 'da');
			customInstance.mockRejectedValue(new Error('offline'));

			const view = await mount();
			await settle(view);

			expect(view.container.textContent).toBe('Log In');
			expect(latest.locales).toHaveLength(1);
			expect(bundleRequests()).toEqual([]);
			await view.unmount();
		});
	});

	describe('when a later request fails or the list changes', () => {
		it('keeps the language when a refetch of the locale list fails', async () => {
			const view = await mount();
			await settle(view);
			await view.flush(() => latest.setLocale('da'));
			await settle(view);

			api.listFails = true;
			await view.flush(() => queryClient.invalidateQueries({ queryKey: ['/translations/locales'] }));
			await settle(view);

			expect(latest.locale).toBe('da');
			expect(view.container.textContent).toBe('Log ind');
			expect(latest.locales).toHaveLength(2);
			expect(latest.error).toBeNull();
			await view.unmount();
		});

		it('shows no error when a refetch of the bundle on screen fails', async () => {
			const view = await mount();
			await settle(view);
			await view.flush(() => latest.setLocale('da'));
			await settle(view);

			api.failing.add('da');
			await view.flush(() => queryClient.invalidateQueries({ queryKey: ['/translations/da'] }));
			await settle(view);

			expect(latest.error).toBeNull();
			expect(view.container.textContent).toBe('Log ind');
			await view.unmount();
		});

		it('lets go of a chosen language that is deactivated, without asking for it again', async () => {
			const view = await mount();
			await settle(view);
			await view.flush(() => latest.setLocale('da'));
			await settle(view);
			customInstance.mockClear();

			api.active = ['en'];
			await view.flush(() => queryClient.invalidateQueries({ queryKey: ['/translations/locales'] }));
			await settle(view);

			expect(latest.locale).toBe('en');
			expect(latest.error).toBeNull();
			expect(bundleRequests()).toEqual([]);
			expect(view.container.textContent).toBe('Log In');
			await view.unmount();
		});

		it('asks again when the language that failed is picked once more', async () => {
			const view = await mount();
			await settle(view);

			api.failing.add('da');
			await view.flush(() => latest.setLocale('da'));
			await settle(view);

			expect(latest.error).toEqual({ locale: 'da' });

			api.failing.delete('da');
			await view.flush(() => latest.setLocale('da'));
			await settle(view);

			expect(latest.error).toBeNull();
			expect(view.container.textContent).toBe('Log ind');
			expect(bundleRequests()).toEqual(['/translations/da', '/translations/da']);
			await view.unmount();
		});
	});

	describe('t()', () => {
		it('fills a label the bundle lacks from the English snapshot', async () => {
			const view = await mount();
			await settle(view);
			await view.flush(() => latest.setLocale('da'));
			await settle(view);

			expect(latest.t('account.log_in')).toBe('Log ind');
			expect(latest.t('account.sign_up')).toBe('Sign Up');
			await view.unmount();
		});
	});
});

describe('useTranslation without a provider', () => {
	it('serves English from the snapshot', async () => {
		const view = await renderWithAct(<Probe />);

		expect(view.container.textContent).toBe('Log In');
		expect(latest.locales).toHaveLength(1);
		expect(latest.error).toBeNull();
		await view.unmount();
	});
});
