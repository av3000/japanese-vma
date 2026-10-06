import { ARTICLE_STATUS } from '@/api/articles/articleStatus';
import type { ArticleModerationItemResource } from '@/api/generated/model';
import { fixtureUuid, UNBROKEN_TITLE } from './dashboardFixtures';

/* Review-queue rows shaped like `GET /articles/pending` (ArticleModerationItemResource). */

let nextId = 1;

export const makeReviewItem = (
	overrides: Partial<ArticleModerationItemResource> = {},
): ArticleModerationItemResource => {
	const id = nextId++;

	return {
		uuid: fixtureUuid('r0000000', id),
		title_jp: '審査待ちの記事',
		status: ARTICLE_STATUS.PENDING,
		status_label: 'Pending',
		hashtags: [{ id: 4, content: 'grammar', created_at: null, updated_at: null }],
		created_at: '2026-10-01T09:00:00+00:00',
		...overrides,
	};
};

export const reviewQueue: ArticleModerationItemResource[] = [
	makeReviewItem(),
	makeReviewItem({ title_jp: 'レビュー中の記事', status: ARTICLE_STATUS.REVIEWING, status_label: 'Under Review' }),
	makeReviewItem({ title_jp: UNBROKEN_TITLE, hashtags: [] }),
	makeReviewItem({
		title_jp: 'タグの多い記事',
		hashtags: Array.from({ length: 10 }, (_, index) => ({
			id: 100 + index,
			content: `tag-${'x'.repeat(40)}-${index}`,
			created_at: null,
			updated_at: null,
		})),
	}),
];
