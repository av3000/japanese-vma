/*
 * Helpers shared by the `Pages/` stories (#581): a paged index answer that honours the request's
 * `page` and `per_page`, and a log of the query strings the mocked endpoint received, so a
 * `play` can assert what a filter or Load more actually sent.
 */
import type { ArticleAppliedCriteriaResource } from '@/api/generated/model';

/** The "applied" block of an article index answer when no filter is set: newest first. */
export const noAppliedArticleCriteria: ArticleAppliedCriteriaResource = {
	q: null,
	filters: {
		jlpt_levels: [],
		hashtag_ids: [],
		kanji_ids: [],
		word_ids: [],
		statuses: [],
		author_uid: null,
		created_from: null,
		created_to: null,
	},
	sort: '-created_at',
};

export interface PageOfRows<Row> {
	items: Row[];
	pagination: { page: number; per_page: number; total: number; last_page: number; has_more: boolean };
}

/**
 * One page of an index of `total` rows, built on demand: `makeRow` gets the 1-based position of
 * each row in the whole result set, so rows stay unique across pages. Reads `page` and `per_page`
 * from the request, falling back to `defaultPerPage`.
 */
export const pageOf = <Row>(
	request: Request,
	total: number,
	makeRow: (position: number) => Row,
	defaultPerPage: number,
): PageOfRows<Row> => {
	const params = new URL(request.url).searchParams;
	const page = Math.max(1, Number(params.get('page') ?? 1));
	const perPage = Math.max(1, Number(params.get('per_page') ?? defaultPerPage));
	const first = (page - 1) * perPage + 1;
	const last = Math.min(total, page * perPage);

	return {
		items: Array.from({ length: Math.max(0, last - first + 1) }, (_, index) => makeRow(first + index)),
		pagination: {
			page,
			per_page: perPage,
			total,
			last_page: Math.max(1, Math.ceil(total / perPage)),
			has_more: page * perPage < total,
		},
	};
};

/** The query strings an endpoint received since the last `clear()`, oldest first. */
export const createRequestLog = () => {
	const searches: URLSearchParams[] = [];

	return {
		searches,
		/** Records the request's query string and returns it, so a handler can answer from it. */
		record: (request: Request) => {
			const params = new URL(request.url).searchParams;
			searches.push(params);

			return params;
		},
		clear: () => {
			searches.length = 0;
		},
		/** The most recent query string, as a plain object of its first values. */
		last: () => Object.fromEntries((searches.at(-1) ?? new URLSearchParams()).entries()),
	};
};
