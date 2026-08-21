import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { SavedListType } from '@/shared/constants/enums';
import KanjiDetails from './index';

const authorizedWidgetProps: Array<{
	entityId: number;
	instanceObjectType: SavedListType;
	isKnownType: SavedListType;
	modalTitle?: string;
	initialIsBookmarked?: boolean;
	initialIsKnown?: boolean;
	loadOnMount?: boolean;
}> = [];
let isAuthenticated = true;

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');

	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useParams: () => ({ kanji_id: 'kanji-uuid' }),
	};
});

vi.mock('@/api/kanjis/details', () => ({
	useKanjiQuery: () => ({
		data: {
			id: 88,
			uuid: 'kanji-uuid',
			character: '水',
			onyomi: ['スイ'],
			kunyomi: ['みず'],
			meanings: ['water', 'river'],
			nanori: [],
			grade: '1',
			stroke_count: 4,
			jlpt: '5',
			frequency: 2,
			radicals: ['水'],
			radical_parts: ['水'],
			viewer_catalogue_state: { is_saved: true, is_known: false },
			display: {
				meaning: 'water, river',
				onyomi: 'スイ',
				kunyomi: 'みず',
				nanori: '',
				radicalParts: '水',
				radicals: '水',
				grade: '1',
				jlpt: '5',
				frequency: '2',
			},
			related: {
				words: [
					{
						id: 501,
						uuid: 'word-uuid',
						word: '水泳',
						furigana: 'すいえい',
						jlpt: 'N5',
						meanings: ['swimming'],
					},
				],
				wordTotal: 1,
				sentences: [
					{
						id: 601,
						uuid: 'sentence-uuid',
						content: '水を飲みます。',
						tatoeba_entry: '12345',
					},
				],
				sentenceTotal: 1,
				articles: [
					{
						id: 701,
						uuid: 'article-uuid',
						title_jp: '水の記事',
						hashtags: [],
						engagement: { stats: null },
					},
				],
				articleTotal: 1,
			},
		},
		isLoading: false,
		isError: false,
	}),
}));

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({
		isAuthenticated,
	}),
}));

vi.mock('@/components/features/catalogues/AuthorizedBookmarkWidget', () => ({
	AuthorizedBookmarkWidget: (props: {
		entityId: number;
		instanceObjectType: SavedListType;
		isKnownType: SavedListType;
		modalTitle?: string;
		initialIsBookmarked?: boolean;
		initialIsKnown?: boolean;
		loadOnMount?: boolean;
	}) => {
		authorizedWidgetProps.push(props);
		return <div>Authorized bookmark widget</div>;
	},
}));

vi.mock('@/assets/images/spinner.gif', () => ({
	default: 'spinner.gif',
}));

describe('KanjiDetails', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		authorizedWidgetProps.length = 0;
		isAuthenticated = true;
	});

	it('renders migrated v1 kanji detail data', () => {
		const html = renderToStaticMarkup(<KanjiDetails />);

		expect(html).toContain('<h1>水</h1>');
		expect(html).toContain('Kunyomi: みず');
		expect(html).toContain('Onyomi: スイ');
		expect(html).toContain('<h2>water, river</h2>');
		expect(html).toContain('JLPT: 5');
		expect(html.indexOf('Kunyomi: みず')).toBeLessThan(html.indexOf('Onyomi: スイ'));
		expect(html.indexOf('Onyomi: スイ')).toBeLessThan(html.indexOf('<h2>water, river</h2>'));
	});

	it('uses numeric kanji id for catalogue actions', () => {
		renderToStaticMarkup(<KanjiDetails />);

		expect(authorizedWidgetProps[0]).toMatchObject({
			entityId: 88,
			instanceObjectType: SavedListType.KANJIS,
			isKnownType: SavedListType.KNOWNKANJIS,
			modalTitle: 'Choose Kanji List to add',
			initialIsBookmarked: true,
			initialIsKnown: false,
			loadOnMount: false,
		});
	});

	it('renders related words sentences and articles from the v1 detail aggregate', () => {
		const html = renderToStaticMarkup(<KanjiDetails />);

		expect(html).toContain('Found in (1) words');
		expect(html).toContain('水泳');
		expect(html).toContain('<h3>swimming</h3>');
		expect(html).toContain('JLPT: N5');
		expect(html).toContain('/word/word-uuid');
		expect(html).toContain('Found in (1) sentences');
		expect(html).toContain('水を飲みます。');
		expect(html).toContain('/sentence/sentence-uuid');
		expect(html).toContain('Found in (1) articles');
		expect(html).toContain('水の記事');
		expect(html).toContain('/articles/article-uuid');
		expect(html.match(/post-preview d-flex justify-content-between/g)).toHaveLength(3);
		expect(html.match(/relatedResource/g)).toHaveLength(3);
	});

	it('keeps core and related detail public without catalogue actions for guests', () => {
		isAuthenticated = false;

		const html = renderToStaticMarkup(<KanjiDetails />);

		expect(html).toContain('<h2>water, river</h2>');
		expect(html).toContain('Found in (1) words');
		expect(html).not.toContain('Authorized bookmark widget');
		expect(authorizedWidgetProps).toHaveLength(0);
	});
});
