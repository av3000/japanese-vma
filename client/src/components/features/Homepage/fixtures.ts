/** Typed fixtures for the landing-page stories and tests. Not imported by application code. */
import type { ArticleResource, CatalogueResource } from '@/api/generated/model';

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
