// @vitest-environment jsdom
import { act } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { searchCountsIndex } from '@/api/generated/search-counts/search-counts';
import { renderWithAct } from '@/test/renderWithAct';
import { searchCountsKeys, useSearchCounts, type SearchCounts } from './useSearchCounts';

vi.mock('@/api/generated/search-counts/search-counts', async () => {
	const actual = await vi.importActual<typeof import('@/api/generated/search-counts/search-counts')>(
		'@/api/generated/search-counts/search-counts',
	);
	return { ...actual, searchCountsIndex: vi.fn() };
});

const auth = vi.hoisted(() => ({ user: null as { uuid: string } | null, isLoading: false }));
vi.mock('@/hooks/useAuth', () => ({ useAuth: () => auth }));

const COUNTS: SearchCounts = { articles: 0, kanji: 108, words: 1256, sentences: 0, radicals: 1 };

let latest: { counts: SearchCounts | null };
let view: Awaited<ReturnType<typeof renderWithAct>>;

const Probe = ({ query }: { query: string }) => {
	latest = useSearchCounts(query);
	return null;
};

const flush = () => act(() => new Promise((resolve) => setTimeout(resolve, 0)));

const mount = async (query: string) => {
	const client = new QueryClient();
	view = await renderWithAct(
		<QueryClientProvider client={client}>
			<Probe query={query} />
		</QueryClientProvider>,
	);
	await flush();
};

beforeEach(() => {
	vi.mocked(searchCountsIndex).mockReset();
	auth.user = null;
	auth.isLoading = false;
});

afterEach(async () => {
	await view.unmount();
});

describe('useSearchCounts', () => {
	it('asks for the trimmed query and returns the counts without the echo', async () => {
		vi.mocked(searchCountsIndex).mockResolvedValue({ q: 'water', ...COUNTS });

		await mount('  water ');

		expect(searchCountsIndex).toHaveBeenCalledWith({ q: 'water' }, undefined, expect.any(AbortSignal));
		expect(latest.counts).toEqual(COUNTS);
	});

	it('asks nothing for an empty query', async () => {
		await mount('   ');

		expect(searchCountsIndex).not.toHaveBeenCalled();
		expect(latest.counts).toBeNull();
	});

	it('trims invisible characters the way the server does, so the echo still matches', async () => {
		vi.mocked(searchCountsIndex).mockResolvedValue({ q: 'water', ...COUNTS });

		await mount('​water​ ');

		expect(searchCountsIndex).toHaveBeenCalledWith({ q: 'water' }, undefined, expect.any(AbortSignal));
		expect(latest.counts).toEqual(COUNTS);
	});

	it('waits while a stored session restores, so the viewer is known before anything is cached', async () => {
		auth.isLoading = true;
		vi.mocked(searchCountsIndex).mockResolvedValue({ q: 'water', ...COUNTS });

		await mount('water');

		expect(searchCountsIndex).not.toHaveBeenCalled();
		expect(latest.counts).toBeNull();
	});

	it('drops an answer that echoes a different query', async () => {
		vi.mocked(searchCountsIndex).mockResolvedValue({ q: 'wat', ...COUNTS });

		await mount('water');

		expect(latest.counts).toBeNull();
	});

	it('gives up after one failed request, leaving the caller on detection', async () => {
		vi.mocked(searchCountsIndex).mockRejectedValue(new Error('429'));

		await mount('water');
		await flush();

		expect(searchCountsIndex).toHaveBeenCalledTimes(1);
		expect(latest.counts).toBeNull();
	});

	it('keys the counts by viewer, because the article count depends on who asks', () => {
		expect(searchCountsKeys.counts('water', null)).not.toEqual(searchCountsKeys.counts('water', 'user-uuid'));
		expect(searchCountsKeys.counts('water', 'user-uuid')).toEqual([
			'/search/counts',
			{ q: 'water' },
			{ viewer: 'user-uuid' },
		]);
	});
});
