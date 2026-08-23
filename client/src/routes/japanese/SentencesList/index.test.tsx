import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useInfiniteSentences } from '@/api/sentences/hooks/useInfiniteSentences';
import SentencesList from './index';

const fetchNextPageMock = vi.fn();
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

vi.mock('@/api/sentences/hooks/useInfiniteSentences', () => ({ useInfiniteSentences: vi.fn() }));

const useInfiniteSentencesMock = vi.mocked(useInfiniteSentences);
const loadedState = {
	sentences: [{
		id: 1,
		uuid: 'sentence-uuid',
		user_id: null,
		tatoeba_entry: '1001',
		content: '私は学生です。',
	}],
	total: 1,
	isLoading: false,
	isFetchingNextPage: false,
	hasNextPage: false,
	fetchNextPage: fetchNextPageMock,
	error: null,
} as unknown as ReturnType<typeof useInfiniteSentences>;

describe('SentencesList', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		searchParams = new URLSearchParams();
		useInfiniteSentencesMock.mockReturnValue(loadedState);
	});

	it('derives URL filters and uses UUID detail links without a no-op action', () => {
		searchParams = new URLSearchParams('keyword=student');
		const html = renderToStaticMarkup(<SentencesList />);

		expect(useInfiniteSentencesMock).toHaveBeenCalledWith({
			filters: { keyword: 'student', per_page: 10 },
		});
		expect(html).toContain('/sentence/sentence-uuid');
		expect(html).toContain('Tatoeba entry');
		expect(html).not.toContain('Add to List');
	});

	it('renders loading and failure states distinctly', () => {
		useInfiniteSentencesMock.mockReturnValueOnce({
			...loadedState,
			isLoading: true,
		} as ReturnType<typeof useInfiniteSentences>);
		expect(renderToStaticMarkup(<SentencesList />)).toContain('Loading...');

		useInfiniteSentencesMock.mockReturnValueOnce({
			...loadedState,
			error: new Error('failed'),
		} as ReturnType<typeof useInfiniteSentences>);
		expect(renderToStaticMarkup(<SentencesList />)).toContain('could not be loaded');
	});
});
