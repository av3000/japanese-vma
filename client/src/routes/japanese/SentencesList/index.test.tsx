import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
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

vi.mock('@/api/sentences/hooks/useInfiniteSentences', () => ({
	useInfiniteSentences: vi.fn(() => ({
		sentences: [
			{
				id: 1,
				uuid: 'sentence-uuid',
				user_id: null,
				tatoeba_entry: '1001',
				content: '私は学生です。',
			},
		],
		total: 1,
		isLoading: false,
		isFetchingNextPage: false,
		hasNextPage: false,
		fetchNextPage: fetchNextPageMock,
		error: null,
	})),
}));

vi.mock('@/components/features/japanese/sentence/SentenceItem', () => ({
	default: ({
		id,
		sentence,
		tatoeba_entry,
	}: {
		id: string | number;
		sentence: string;
		tatoeba_entry?: string | number;
	}) => (
		<article>
			<a href={`/sentence/${id}`}>{sentence}</a>
			<span>{tatoeba_entry}</span>
		</article>
	),
}));

describe('SentencesList', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		searchParams = new URLSearchParams();
	});

	it('renders sentences from the v1 query hook', () => {
		const html = renderToStaticMarkup(<SentencesList />);

		expect(html).toContain('私は学生です。');
		expect(html).toContain('/sentence/1');
		expect(html).toContain('1001');
		expect(html).toContain('Results total:');
		expect(html).toContain('1');
	});

	it('keeps the sentence list route free of legacy loading copy when data is loaded', () => {
		const html = renderToStaticMarkup(<SentencesList />);

		expect(html).not.toContain('Loading...');
	});
});
