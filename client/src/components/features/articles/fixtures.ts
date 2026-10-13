import type { ArticleFacetResource } from '@/api/generated/model/articleFacetResource';
import type { KanjiListResource } from '@/api/generated/model/kanjiListResource';
import type { WordListResource } from '@/api/generated/model/wordListResource';
import { kanjiRows, repeatRows, wordRows } from '@/components/shared/DataTable/DataTable.fixtures';

/** The facets the article index returns when asked for them: server counts per JLPT level and hashtag. */
export const articleFacets: ArticleFacetResource[] = [
	{
		key: 'jlpt_levels',
		label: 'JLPT level',
		type: 'multi',
		values: [
			{ key: 'n5', label: 'N5', count: 12, selected: false },
			{ key: 'n4', label: 'N4', count: 8, selected: false },
			{ key: 'n3', label: 'N3', count: 21, selected: true },
			{ key: 'n2', label: 'N2', count: 5, selected: false },
			{ key: 'n1', label: 'N1', count: 0, selected: false },
			{ key: 'uncommon', label: 'Uncommon', count: 2, selected: false },
		],
	},
	{
		key: 'hashtag_ids',
		label: 'Hashtags',
		type: 'multi',
		values: [
			{ key: '7', label: 'grammar', count: 3, selected: true },
			{ key: '9', label: 'news', count: 14, selected: false },
			{ key: '12', label: 'trains', count: 4, selected: false },
		],
	},
];

/**
 * One page of an index of `total` rows built from the shared dictionary fixtures, honouring the
 * request's `page` and `per_page`; a `keyword` narrows it to three matches.
 */
const pageOf = <Row extends { id: number; uuid: string }>(rows: readonly Row[], total: number, url: string) => {
	const params = new URL(url).searchParams;
	const page = Number(params.get('page') ?? 1);
	const perPage = Number(params.get('per_page') ?? 20);
	const matching = params.get('keyword') ? Math.min(3, total) : total;

	return {
		items: repeatRows(rows, matching).slice((page - 1) * perPage, page * perPage),
		pagination: {
			page,
			per_page: perPage,
			total: matching,
			last_page: Math.max(1, Math.ceil(matching / perPage)),
			has_more: page * perPage < matching,
		},
	};
};

/** Resolvers for the generated kanji and word index handlers, for an article with `total` of each. */
export const kanjiIndexPage =
	(total: number) =>
	({ request }: { request: Request }): KanjiListResource =>
		pageOf(kanjiRows, total, request.url);

export const wordIndexPage =
	(total: number) =>
	({ request }: { request: Request }): WordListResource =>
		pageOf(wordRows, total, request.url);
