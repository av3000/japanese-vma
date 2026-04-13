import { describe, expect, it } from 'vitest';
import type { ArticleDetailResourceArticle } from '@/api/generated/model/articleDetailResourceArticle';
import { mapArticleDetail } from './details';

const createArticle = (overrides: Partial<ArticleDetailResourceArticle> = {}): ArticleDetailResourceArticle => ({
	id: 123,
	uid: 'article-uuid',
	entity_type_uid: 'entity-type-uuid',
	title_jp: '日本語の記事',
	title_en: 'Japanese article',
	content_jp: 'これはテスト記事です。',
	content_en: 'This is a test article.',
	source_link: 'https://example.com/article',
	publicity: 1,
	status: 3,
	jlpt_levels: {
		n1: 0,
		n2: 1,
		n3: 2,
		n4: 3,
		n5: 4,
		uncommon: 5,
	},
	author: {
		id: 7,
		name: 'Aki',
	},
	hashtags: [],
	created_at: '2026-04-01T12:00:00.000Z',
	updated_at: '2026-04-02T12:00:00.000Z',
	engagement: {
		is_liked_by_viewer: true,
		likes_count: 2,
		views_count: 5,
		downloads_count: 1,
	},
	kanjis: [],
	words: [],
	processing_status: undefined,
	...overrides,
});

describe('mapArticleDetail', () => {
	it('maps generated detail article data to the route-facing article shape', () => {
		const article = mapArticleDetail(createArticle());

		expect(article.uuid).toBe('article-uuid');
		expect(article.displayName).toBe('Aki');
		expect(article.formattedDate).toBe(new Date('2026-04-01T12:00:00.000Z').toLocaleDateString());
		expect(article.engagement?.likes_count).toBe(2);
		expect(article.words).toEqual([]);
	});

	it('falls back to an unknown author display name when the generated author is absent', () => {
		const article = mapArticleDetail(createArticle({ author: undefined as any }));

		expect(article.displayName).toBe('Unknown Author');
	});
});
