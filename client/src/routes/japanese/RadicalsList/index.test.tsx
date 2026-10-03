import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useInfiniteRadicals } from '@/api/radicals/hooks/useInfiniteRadicals';
import RadicalsList from './index';

const setSearchParamsMock = vi.fn();
let searchParams = new URLSearchParams();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useSearchParams: () => [searchParams, setSearchParamsMock],
	};
});
vi.mock('@/api/radicals/hooks/useInfiniteRadicals', async () => {
	const actual = await vi.importActual<typeof import('@/api/radicals/hooks/useInfiniteRadicals')>(
		'@/api/radicals/hooks/useInfiniteRadicals',
	);
	return { ...actual, useInfiniteRadicals: vi.fn() };
});

const useInfiniteRadicalsMock = vi.mocked(useInfiniteRadicals);

const queryResult = (overrides: Record<string, unknown> = {}) =>
	({
		radicals: [{ id: 9, uuid: 'radical-uuid', radical: '水', strokes: 4, meaning: 'water', hiragana: 'みず' }],
		total: 1,
		isLoading: false,
		isFetchingNextPage: false,
		hasNextPage: false,
		fetchNextPage: vi.fn(),
		isError: false,
		...overrides,
	}) as unknown as ReturnType<typeof useInfiniteRadicals>;

describe('RadicalsList', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		searchParams = new URLSearchParams();
		useInfiniteRadicalsMock.mockReturnValue(queryResult());
	});

	it('derives the URL keyword and uses the UUID detail link', () => {
		searchParams = new URLSearchParams('keyword=water');
		const html = renderToStaticMarkup(<RadicalsList />);

		expect(useInfiniteRadicalsMock).toHaveBeenCalledWith({ filters: { keyword: 'water', per_page: 25 } });
		expect(html).toMatch(/<th role="rowheader" scope="row"[^>]*><a[^>]*href="\/radical\/radical-uuid"/);
	});

	it('renders PageHeader and FilterBar in place of the legacy search bar', () => {
		searchParams = new URLSearchParams('keyword=water');
		const html = renderToStaticMarkup(<RadicalsList />);

		expect(html.match(/<h1[ >]/g)).toHaveLength(1);
		expect(html).toMatch(/<p[^>]*>Showing 1 of 1 · keyword: water<\/p>/);
		expect(html).toContain('role="search" aria-label="Radical filters"');
		expect(html).not.toContain('Results total');
	});

	it('shows labelled dashes for the nullable radical fields', () => {
		useInfiniteRadicalsMock.mockReturnValue(
			queryResult({
				radicals: [{ id: 10, uuid: 'r2', radical: '鬯', strokes: null, meaning: null, hiragana: null }],
			}),
		);

		const html = renderToStaticMarkup(<RadicalsList />);

		for (const label of ['No meaning', 'No reading', 'No stroke count']) {
			expect(html).toContain(`>${label}</span>`);
		}
	});

	it('gives rows without a glyph distinct link names', () => {
		useInfiniteRadicalsMock.mockReturnValue(
			queryResult({
				radicals: [
					{ id: 11, uuid: 'r3', radical: null, strokes: 3, meaning: 'water', hiragana: null },
					{ id: 12, uuid: 'r4', radical: null, strokes: 4, meaning: null, hiragana: null },
				],
			}),
		);

		const html = renderToStaticMarkup(<RadicalsList />);

		expect(html).toContain('>Radical without a glyph: water</span>');
		expect(html).toContain('>Radical without a glyph, #12</span>');
	});

	it('shows skeleton rows while loading and the alert on failure', () => {
		useInfiniteRadicalsMock.mockReturnValueOnce(queryResult({ radicals: [], isLoading: true }));
		expect(renderToStaticMarkup(<RadicalsList />)).toMatch(
			/<table role="table" aria-label="Radicals" aria-busy="true"/,
		);

		useInfiniteRadicalsMock.mockReturnValueOnce(queryResult({ radicals: [], isError: true }));
		expect(renderToStaticMarkup(<RadicalsList />)).toContain('Unable to load radicals.');
	});
});
