import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { SavedListType } from '@/shared/constants/enums';
import WordDetails from './index';

const useWordQueryMock = vi.fn();
const authorizedWidgetProps: Array<Record<string, unknown>> = [];

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useParams: () => ({ word_id: 'word-route-uuid' }),
	};
});

vi.mock('@/api/words/details', () => ({
	useWordQuery: (...args: unknown[]) => useWordQueryMock(...args),
}));

vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ isAuthenticated: true }) }));
vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));
vi.mock('@/components/features/catalogues/AuthorizedBookmarkWidget', () => ({
	AuthorizedBookmarkWidget: (props: Record<string, unknown>) => {
		authorizedWidgetProps.push(props);
		return <div>Bookmark</div>;
	},
}));
vi.mock('@/components/shared/Chip', () => ({
	Chip: ({ children }: { children: ReactNode }) => <span>{children}</span>,
}));

const loadedWord = {
	id: 42,
	uuid: 'word-uuid',
	word: '水',
	furigana: 'みず',
	word_type: 'noun',
	jlpt: 'N5',
	meaning: 'water',
	meanings: ['water'],
	kanjis: [{ uuid: 'kanji-uuid', character: '水', meanings: ['water'] }],
	articles: [{
		uuid: 'article-uuid',
		title_jp: '水の記事',
		hashtags: [{ id: 1, content: '#water' }],
		views_total: 3,
		likes_total: 2,
		comments_total: 1,
	}],
};

describe('WordDetails', () => {
	beforeEach(() => {
		authorizedWidgetProps.length = 0;
		useWordQueryMock.mockReturnValue({ data: loadedWord, isLoading: false, isError: false });
	});

	it('passes the UUID route identifier and uses response identifiers in content', () => {
		const html = renderToStaticMarkup(<WordDetails />);

		expect(useWordQueryMock).toHaveBeenCalledWith('word-route-uuid');
		expect(authorizedWidgetProps[0]).toMatchObject({
			entityId: 42,
			instanceObjectType: SavedListType.WORDS,
			isKnownType: SavedListType.KNOWNWORDS,
		});
		expect(html).toContain('/kanji/kanji-uuid');
		expect(html).toContain('/articles/article-uuid');
		expect(html).toContain('#water');
		expect(html).toContain('Views: 3');
	});

	it('renders loading and error states distinctly', () => {
		useWordQueryMock.mockReturnValueOnce({ isLoading: true, isError: false });
		expect(renderToStaticMarkup(<WordDetails />)).toContain('Loading...');

		useWordQueryMock.mockReturnValueOnce({ isLoading: false, isError: true });
		expect(renderToStaticMarkup(<WordDetails />)).toContain('Unable to load word.');
	});
});
