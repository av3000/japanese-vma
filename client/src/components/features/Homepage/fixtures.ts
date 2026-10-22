/** Typed fixtures for the landing-page and Library Cards stories and tests. Not imported by application code. */
import type {
	ArticleResource,
	CatalogueResource,
	HashtagResource,
	ProcessingStatus,
	ProcessingStatusResource,
} from '@/api/generated/model';

const author = { id: 7, name: 'Hanako', uuid: 'author-uuid' };

export const makeArticle = (overrides: Partial<ArticleResource> = {}): ArticleResource => ({
	id: 1,
	uuid: 'article-1',
	entity_type_uid: 'article',
	title_jp: '春の京都を歩く',
	title_en: 'Walking through Kyoto in spring',
	content_preview_jp: '',
	content_preview_en: '',
	source_link: '',
	origin: 'user',
	source: null,
	publicity: 1,
	status: 1,
	jlpt_levels: { n1: 1, n2: 3, n3: 9, n4: 6, n5: 12, uncommon: 1 },
	author,
	hashtags: [],
	created_at: '2026-09-20T09:00:00Z',
	updated_at: '2026-09-20T09:00:00Z',
	engagement: { stats: null },
	kanjis: [],
	processing_status: null,
	...overrides,
});

/** A long but realistic news headline, to check the two-line clamp. */
export const LONGEST_JAPANESE_TITLE =
	'東京都、来年4月から高校生の通学定期代を全額補助へ　物価高で家計の負担軽減　対象は約30万人、所得制限は設けず';

export const latestArticles: ArticleResource[] = [
	makeArticle({ uuid: 'article-1', title_jp: LONGEST_JAPANESE_TITLE, created_at: '2026-09-24T08:00:00Z' }),
	makeArticle({
		uuid: 'article-2',
		title_jp: '新しい駅が開業',
		jlpt_levels: { n1: 0, n2: 0, n3: 2, n4: 5, n5: 8, uncommon: 0 },
		created_at: '2026-09-22T08:00:00Z',
	}),
	makeArticle({
		uuid: 'article-3',
		title_jp: '台風14号、週末に九州へ接近のおそれ',
		jlpt_levels: { n1: 4, n2: 11, n3: 6, n4: 3, n5: 2, uncommon: 5 },
		created_at: '2026-09-18T08:00:00Z',
	}),
	makeArticle({
		uuid: 'article-4',
		title_jp: '処理中の記事',
		// Still processing: all zero, so the bar renders nothing.
		jlpt_levels: { n1: 0, n2: 0, n3: 0, n4: 0, n5: 0, uncommon: 0 },
		created_at: '2025-12-30T08:00:00Z',
	}),
];

export const makeCatalogue = (overrides: Partial<CatalogueResource> = {}): CatalogueResource => ({
	id: 1,
	uuid: 'catalogue-1',
	type: 6,
	type_label: 'Kanji',
	title: 'N3 kanji for news reading',
	description: null,
	publicity: 1,
	owner: author,
	items_count: 42,
	hashtags: [],
	engagement: { likes_count: '12', views_count: '1532', downloads_count: '3', comments_count: '2' },
	jlpt_levels: null,
	created_at: '2026-09-01T09:00:00Z',
	updated_at: '2026-09-01T09:00:00Z',
	...overrides,
});

export const popularCatalogues: CatalogueResource[] = [
	makeCatalogue(),
	makeCatalogue({
		uuid: 'catalogue-2',
		type: 8,
		type_label: 'Sentences',
		title: '日常会話でよく使う例文集：買い物・レストラン・駅での会話を中心に集めました',
		items_count: 1,
		engagement: { likes_count: '4', views_count: '980', downloads_count: '0', comments_count: '0' },
	}),
	makeCatalogue({ uuid: 'catalogue-3', type: 7, type_label: 'Words', title: 'Cooking verbs', items_count: 118 }),
	makeCatalogue({
		uuid: 'catalogue-4',
		type: 9,
		type_label: 'Articles',
		title: 'Weekend reading',
		items_count: 6,
		engagement: null,
	}),
];

export const makeHashtags = (contents: string[]): HashtagResource[] =>
	contents.map((content, index) => ({ id: index + 1, content, created_at: null, updated_at: null }));

export const makeProcessingStatus = (status: ProcessingStatus): ProcessingStatusResource => ({
	id: 1,
	entity_id: 'article-1',
	type: 'article_content_processing',
	status,
	sequence: 1,
	attempt: 1,
	max_attempts: 3,
	metadata: {},
	created_at: '2026-09-19T08:00:00Z',
	updated_at: '2026-09-19T08:00:00Z',
});

const NO_JLPT_COUNTS = { n1: 0, n2: 0, n3: 0, n4: 0, n5: 0, uncommon: 0 };

/** Hostile-data articles for the Library Cards (#381). */
export const libraryArticles = {
	longest: makeArticle({
		uuid: 'article-longest',
		title_jp: LONGEST_JAPANESE_TITLE,
		title_en:
			'Tokyo to fully subsidise high-school commuter passes from next April; about 300,000 students, no income cap',
		jlpt_levels: { n1: 6, n2: 14, n3: 22, n4: 18, n5: 25, uncommon: 4 },
		hashtags: makeHashtags([
			'#news',
			'#tokyo',
			'#education',
			'#economy',
			'#commuting',
			'#policy',
			'#subsidy',
			'#students',
		]),
		engagement: { stats: { views_count: '1284', comments_count: '37', likes_count: '212', downloads_count: '0' } },
	}),
	/** Kana-only title, no English title, no tags, nothing counted: the card must still look complete. */
	zeroEverything: makeArticle({
		uuid: 'article-zero',
		title_jp: 'いっしょにあそぼう',
		title_en: undefined,
		jlpt_levels: NO_JLPT_COUNTS,
		hashtags: [],
		engagement: { stats: null },
	}),
	processing: makeArticle({
		uuid: 'article-processing',
		title_jp: '台風14号、週末に九州へ接近のおそれ',
		title_en: 'Typhoon No. 14 may approach Kyushu at the weekend',
		jlpt_levels: NO_JLPT_COUNTS,
		hashtags: makeHashtags(['#weather']),
		processing_status: makeProcessingStatus('processing'),
	}),
};

/** Hostile-data catalogues for the Library Cards (#381). */
export const libraryCatalogues = {
	kanji: makeCatalogue({
		uuid: 'catalogue-kanji',
		description: 'The kanji that keep showing up in NHK Easy articles, ordered by how often they appear.',
		items_count: 128,
		hashtags: makeHashtags(['#jlpt', '#news']),
		jlpt_levels: { n1: 4, n2: 12, n3: 61, n4: 30, n5: 21, uncommon: 0 },
		engagement: { views_count: '342', comments_count: '4', likes_count: '21', downloads_count: '18' },
	}),
	longest: makeCatalogue({
		uuid: 'catalogue-longest',
		type: 8,
		type_label: 'Sentences',
		title: 'Sentences that trip me up: the は versus が collection, with notes on every example and links back to the articles they came from',
		description:
			'A very long description that keeps going to check that the card clamps it to two lines instead of pushing the stats footer out of the card and making the grid rows uneven.',
		owner: { id: 8, uuid: 'owner-long', name: 'A-very-long-display-name-without-spaces-that-keeps-going' },
		items_count: 1203,
		hashtags: makeHashtags(['#grammar', '#particles', '#notes', '#study', '#jlpt-n3']),
		jlpt_levels: { n1: 40, n2: 88, n3: 310, n4: 205, n5: 190, uncommon: 72 },
		engagement: { views_count: '12408', comments_count: '96', likes_count: '870', downloads_count: '412' },
	}),
	/** A Radicals catalogue with no items: no JLPT data, no description, no tags, no engagement. */
	empty: makeCatalogue({
		uuid: 'catalogue-empty',
		type: 5,
		type_label: 'Radicals',
		title: 'Radicals to learn first',
		owner: { id: 9, uuid: 'owner-yui', name: 'Yui' },
		items_count: 0,
		jlpt_levels: null,
		engagement: { views_count: '0', comments_count: '0', likes_count: '0', downloads_count: '0' },
	}),
	/** Words carry no JLPT level yet, so every word counts as uncommon and the card shows no bar. */
	wordsUnassigned: makeCatalogue({
		uuid: 'catalogue-words',
		type: 7,
		type_label: 'Words',
		title: '台所の言葉',
		description: 'Kitchen vocabulary for cooking along with Japanese recipe videos.',
		owner: { id: 10, uuid: 'owner-kenji', name: 'Kenji' },
		items_count: 64,
		hashtags: makeHashtags(['#food']),
		jlpt_levels: { ...NO_JLPT_COUNTS, uncommon: 64 },
	}),
};
