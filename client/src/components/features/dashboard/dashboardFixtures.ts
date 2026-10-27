import { ARTICLE_STATUS } from '@/api/articles/articleStatus';
import type { ArticleResource, ProcessingStatusResource } from '@/api/generated/model';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import { PUBLICITY } from '@/api/publicity';

/*
 * Dashboard article rows shaped like the real owner payload (`GET /articles?author_uid=…`),
 * including the hostile cases from UI-DASH-00 (#450): every approval × processing combination, a
 * 255-character unbroken title, an article with zero of everything, and 500 rows.
 */

export const fixtureOwner = { id: 7, name: 'Hanako', uuid: 'owner-uuid' };

export const fixtureUuid = (prefix: string, id: number) => `${prefix}-0000-4000-8000-${String(id).padStart(12, '0')}`;

/** 255 characters with no break opportunity: the longest title the forms accept. */
export const UNBROKEN_TITLE = 'あ'.repeat(255);

export const makeProcessing = (status: ProcessingStatus, entityId: string): ProcessingStatusResource => ({
	id: 1,
	entity_id: entityId,
	type: 'article_content_processing',
	status,
	sequence: 1,
	attempt: status === ProcessingStatus.failed ? 3 : 1,
	max_attempts: 3,
	metadata: {},
	created_at: '2026-10-01T09:00:00Z',
	updated_at: '2026-10-01T09:00:00Z',
});

let nextArticleId = 1;

export const makeDashboardArticle = (overrides: Partial<ArticleResource> = {}): ArticleResource => {
	const id = nextArticleId++;
	const articleUuid = overrides.uuid ?? fixtureUuid('a0000000', id);

	return {
		id,
		uuid: articleUuid,
		entity_type_uid: 'article',
		title_jp: '春の京都を歩く',
		title_en: 'Walking through Kyoto in spring',
		content_preview_jp: '',
		content_preview_en: '',
		source_link: '',
		origin: 'user',
		source: null,
		publicity: PUBLICITY.PUBLIC,
		status: ARTICLE_STATUS.APPROVED,
		jlpt_levels: { n1: 1, n2: 3, n3: 9, n4: 6, n5: 12, uncommon: 1 },
		author: fixtureOwner,
		hashtags: [],
		created_at: '2026-09-20T09:00:00Z',
		updated_at: '2026-10-01T12:00:00Z',
		engagement: { stats: { views_count: '128', comments_count: '4', likes_count: '17', downloads_count: '0' } },
		kanjis: [],
		processing_status: makeProcessing(ProcessingStatus.completed, articleUuid),
		...overrides,
	};
};

const APPROVAL_STATUSES = [
	ARTICLE_STATUS.PENDING,
	ARTICLE_STATUS.REVIEWING,
	ARTICLE_STATUS.REJECTED,
	ARTICLE_STATUS.APPROVED,
] as const;

const PROCESSING_STATUSES = [
	ProcessingStatus.pending,
	ProcessingStatus.processing,
	ProcessingStatus.failed,
	ProcessingStatus.completed,
] as const;

/** Every approval status against every processing status, alternating visibility. */
export const everyStateArticles: ArticleResource[] = APPROVAL_STATUSES.flatMap((status, row) =>
	PROCESSING_STATUSES.map((processing, column) => {
		const id = 1000 + row * 10 + column;
		const articleUuid = fixtureUuid('a0000001', id);

		return makeDashboardArticle({
			id,
			uuid: articleUuid,
			title_jp: `記事 ${row + 1}-${column + 1}`,
			title_en: `Status ${status}, processing ${processing}`,
			status,
			publicity: (row + column) % 2 === 0 ? PUBLICITY.PUBLIC : PUBLICITY.PRIVATE,
			processing_status: makeProcessing(processing, articleUuid),
		});
	}),
);

export const hostileArticles: ArticleResource[] = [
	makeDashboardArticle({ title_jp: UNBROKEN_TITLE, title_en: 'x'.repeat(255) }),
	makeDashboardArticle({
		title_jp: '何もない記事',
		title_en: '',
		status: ARTICLE_STATUS.PENDING,
		publicity: PUBLICITY.PRIVATE,
		jlpt_levels: { n1: 0, n2: 0, n3: 0, n4: 0, n5: 0, uncommon: 0 },
		engagement: { stats: null },
		processing_status: null,
	}),
	makeDashboardArticle({
		title_jp: '却下された非公開の記事',
		status: ARTICLE_STATUS.REJECTED,
		publicity: PUBLICITY.PRIVATE,
	}),
];

export const manyArticles = (count: number): ArticleResource[] =>
	Array.from({ length: count }, (_, index) =>
		makeDashboardArticle({
			id: 10_000 + index,
			uuid: fixtureUuid('a0000002', index),
			title_jp: `記事その${index + 1}`,
			title_en: `Article ${index + 1}`,
			status: APPROVAL_STATUSES[index % APPROVAL_STATUSES.length],
		}),
	);
