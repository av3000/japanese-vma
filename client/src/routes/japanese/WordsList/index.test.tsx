import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useInfiniteWords } from '@/api/words/hooks/useInfiniteWords';
import WordsList from './index';

const setSearchParamsMock = vi.fn();
const setQueryDataMock = vi.fn();
const authorizedWidgetProps: Array<Record<string, unknown>> = [];
let searchParams = new URLSearchParams();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useSearchParams: () => [searchParams, setSearchParamsMock],
	};
});
vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
	return { ...actual, useQueryClient: () => ({ setQueryData: setQueryDataMock }) };
});
vi.mock('@/api/words/hooks/useInfiniteWords', async () => {
	const actual = await vi.importActual<typeof import('@/api/words/hooks/useInfiniteWords')>(
		'@/api/words/hooks/useInfiniteWords',
	);
	return { ...actual, useInfiniteWords: vi.fn() };
});
vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ isAuthenticated: true }) }));
vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));
vi.mock('@/components/features/catalogues/AuthorizedBookmarkWidget', () => ({
	AuthorizedBookmarkWidget: (props: Record<string, unknown>) => {
		authorizedWidgetProps.push(props);
		return <span>Bookmark</span>;
	},
}));

const useInfiniteWordsMock = vi.mocked(useInfiniteWords);

describe('WordsList', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		authorizedWidgetProps.length = 0;
		searchParams = new URLSearchParams();
		useInfiniteWordsMock.mockReturnValue({
			words: [{
				id: 42,
				uuid: 'word-uuid',
				word: '水',
				furigana: 'みず',
				word_type: 'noun',
				meaning: 'water',
				jlpt: 'N5',
				viewer_catalogue_state: { is_saved: false, is_known: false },
			}],
			total: 1,
			error: null,
			fetchNextPage: vi.fn(),
			hasNextPage: false,
			isFetchingNextPage: false,
			isPending: false,
			isError: false,
		} as unknown as ReturnType<typeof useInfiniteWords>);
	});

	it('derives URL filters and separates navigation from catalogue identifiers', () => {
		searchParams = new URLSearchParams('keyword=water');
		const html = renderToStaticMarkup(<WordsList />);

		expect(useInfiniteWordsMock).toHaveBeenCalledWith({
			filters: { keyword: 'water', per_page: 10, include: 'viewer_catalogue_state' },
		});
		expect(html).toContain('/word/word-uuid');
		expect(authorizedWidgetProps[0]).toMatchObject({ entityId: 42 });
	});
});
