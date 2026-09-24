import {
	emptyArticleListFilterState,
	serializeArticleListFilterState,
} from '@/routes/ArticlesList/articleListSearchParams';

export const SEARCH_SCOPES = [
	{ value: 'articles', label: 'Articles', path: '/articles', placeholder: 'Search articles' },
	{ value: 'kanji', label: 'Kanji', path: '/kanjis', placeholder: 'Search kanji' },
	{ value: 'words', label: 'Words', path: '/words', placeholder: 'Search words' },
	{ value: 'sentences', label: 'Sentences', path: '/sentences', placeholder: 'Search sentences' },
	{ value: 'radicals', label: 'Radicals', path: '/radicals', placeholder: 'Search radicals' },
] as const;

export type SearchScope = (typeof SEARCH_SCOPES)[number]['value'];

export const DEFAULT_SEARCH_SCOPE: SearchScope = 'articles';

export const findSearchScope = (scope: SearchScope) =>
	SEARCH_SCOPES.find((candidate) => candidate.value === scope) ?? SEARCH_SCOPES[0];

/**
 * The list URL a landing search lands on. The keyword is trimmed; an empty keyword opens the
 * scope's list unfiltered. Articles go through the route's own serializer so the landing page
 * and the list agree on one URL shape; the list applies its own minimum search length.
 */
export const scopedSearchUrl = (scope: SearchScope, keyword: string): string => {
	const { path } = findSearchScope(scope);
	const trimmed = keyword.trim();

	const params =
		scope === 'articles'
			? serializeArticleListFilterState({ ...emptyArticleListFilterState(), q: trimmed })
			: new URLSearchParams(trimmed ? { keyword: trimmed } : {});

	const query = params.toString();
	return query ? `${path}?${query}` : path;
};
