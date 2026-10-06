import { ARTICLE_STATUS, AWAITING_REVIEW_STATUSES } from '@/api/articles/articleStatus';
import type { ArticleStatus } from '@/api/generated/model/articleStatus';
import { MIN_SEARCH_LENGTH } from '@/routes/ArticlesList/articleListSearchParams';
import {
	CATALOGUE_TYPE_FILTER_ALL,
	CATALOGUE_TYPE_FILTER_OPTIONS,
	isCustomCatalogueType,
} from '@/shared/constants/catalogues';

/**
 * URL <-> view state for the Dashboard route (UI-DASH-02, #452).
 *
 * The URL owns which tab is open and that tab's filters, so the view survives reload and
 * back/forward. Each tab link carries only its tab: filters belong to the tab they were set on,
 * and a keyword typed on Articles means nothing on Lists.
 */

export const DASHBOARD_TABS = ['articles', 'lists', 'review'] as const;

export type DashboardTab = (typeof DASHBOARD_TABS)[number];

export const DEFAULT_DASHBOARD_TAB: DashboardTab = 'articles';

export const DASHBOARD_TAB_LABELS: Record<DashboardTab, string> = {
	articles: 'Articles',
	lists: 'Lists',
	review: 'Review queue',
};

/** Tabs a signed-in user may open. Review is the admin-only queue kept until #184 ships. */
export const visibleDashboardTabs = (isAdmin: boolean): readonly DashboardTab[] =>
	isAdmin ? DASHBOARD_TABS : DASHBOARD_TABS.filter((tab) => tab !== 'review');

export const ARTICLE_STATUS_FILTERS = ['all', 'awaiting', 'rejected', 'approved'] as const;

export type ArticleStatusFilter = (typeof ARTICLE_STATUS_FILTERS)[number];

export const DEFAULT_ARTICLE_STATUS_FILTER: ArticleStatusFilter = 'all';

/**
 * What each choice sends as `statuses[]`. "Awaiting review" matches the moderation queue
 * (`ArticleRepository::findModerationQueue`): pending and reviewing. `PROCESSED` is never written
 * by the backend, so it only shows under All.
 */
const ARTICLE_STATUS_FILTER_STATUSES: Record<ArticleStatusFilter, readonly ArticleStatus[]> = {
	all: [],
	awaiting: AWAITING_REVIEW_STATUSES,
	rejected: [ARTICLE_STATUS.REJECTED],
	approved: [ARTICLE_STATUS.APPROVED],
};

export const ARTICLE_STATUS_FILTER_OPTIONS: ReadonlyArray<{ value: ArticleStatusFilter; label: string }> = [
	// The select's label is visually hidden, so the choices name what they filter.
	{ value: 'all', label: 'All statuses' },
	{ value: 'awaiting', label: 'Awaiting review' },
	{ value: 'rejected', label: 'Rejected' },
	{ value: 'approved', label: 'Approved' },
];

export const statusesForFilter = (filter: ArticleStatusFilter): ArticleStatus[] => [
	...ARTICLE_STATUS_FILTER_STATUSES[filter],
];

export const LIST_SORTS = ['new', 'pop'] as const;

export type ListSort = (typeof LIST_SORTS)[number];

export const DEFAULT_LIST_SORT: ListSort = 'new';

export type DashboardViewState = {
	tab: DashboardTab;
	/** The raw keyword as typed; trimming and the minimum length are the request's concern. */
	q: string;
	/** Articles tab only. */
	status: ArticleStatusFilter;
	/** Lists tab only: a custom catalogue type as a select value, or `CATALOGUE_TYPE_FILTER_ALL`. */
	listType: string;
	/** Lists tab only. */
	listSort: ListSort;
};

/**
 * How a tab changes the view. Typing replaces the history entry; a select or a tab pushes one,
 * so back undoes it.
 */
export type DashboardViewChange = (patch: Partial<DashboardViewState>, options?: { replace?: boolean }) => void;

export const defaultDashboardViewState = (tab: DashboardTab = DEFAULT_DASHBOARD_TAB): DashboardViewState => ({
	tab,
	q: '',
	status: DEFAULT_ARTICLE_STATUS_FILTER,
	listType: CATALOGUE_TYPE_FILTER_ALL,
	listSort: DEFAULT_LIST_SORT,
});

const LIST_TYPE_VALUES = new Set(CATALOGUE_TYPE_FILTER_OPTIONS.map((option) => option.value));

const oneOf = <T extends string>(values: readonly T[], raw: string | null, fallback: T): T =>
	raw !== null && (values as readonly string[]).includes(raw) ? (raw as T) : fallback;

/** A Lists sort from the URL or a select, falling back to the default for anything else. */
export const parseListSort = (raw: string | null): ListSort => oneOf(LIST_SORTS, raw, DEFAULT_LIST_SORT);

/**
 * The keyword a request actually sends: trimmed, and empty below the backend minimum
 * (`SearchTerm::MIN_LENGTH`, which both the article and the catalogue index enforce). The
 * empty-state copy reads the same value, so a one-letter search is never reported as a search.
 */
export const appliedSearch = (q: string): string => {
	const search = q.trim();

	return search.length >= MIN_SEARCH_LENGTH ? search : '';
};

const readListType = (raw: string | null): string =>
	raw !== null && LIST_TYPE_VALUES.has(raw) && isCustomCatalogueType(Number(raw)) ? raw : CATALOGUE_TYPE_FILTER_ALL;

/**
 * Unknown or stale values fall back to the defaults instead of erroring: a hand-edited or old
 * URL still opens a working dashboard. `review` falls back to Articles for non-admins.
 */
export const parseDashboardSearchParams = (
	searchParams: URLSearchParams,
	{ isAdmin }: { isAdmin: boolean },
): DashboardViewState => {
	const tab = oneOf(visibleDashboardTabs(isAdmin), searchParams.get('tab'), DEFAULT_DASHBOARD_TAB);
	const state = defaultDashboardViewState(tab);

	if (tab === 'review') {
		return state;
	}

	const q = searchParams.get('q') ?? '';

	if (tab === 'lists') {
		return {
			...state,
			q,
			listType: readListType(searchParams.get('type')),
			listSort: parseListSort(searchParams.get('sort')),
		};
	}

	return {
		...state,
		q,
		status: oneOf(ARTICLE_STATUS_FILTERS, searchParams.get('status'), DEFAULT_ARTICLE_STATUS_FILTER),
	};
};

/**
 * Only the active tab's values are written, and defaults are omitted, so a pristine dashboard
 * is a clean `/dashboard` and two equivalent states never produce two URLs.
 */
export const serializeDashboardViewState = (state: DashboardViewState): URLSearchParams => {
	const params = new URLSearchParams();

	if (state.tab !== DEFAULT_DASHBOARD_TAB) {
		params.set('tab', state.tab);
	}

	if (state.tab === 'review') {
		return params;
	}

	if (state.q !== '') {
		params.set('q', state.q);
	}

	if (state.tab === 'articles' && state.status !== DEFAULT_ARTICLE_STATUS_FILTER) {
		params.set('status', state.status);
	}

	if (state.tab === 'lists') {
		if (state.listType !== CATALOGUE_TYPE_FILTER_ALL) {
			params.set('type', state.listType);
		}

		if (state.listSort !== DEFAULT_LIST_SORT) {
			params.set('sort', state.listSort);
		}
	}

	return params;
};

/** The `search` part of a tab link: the tab alone, with that tab's filters reset. */
export const dashboardTabSearch = (tab: DashboardTab): string => {
	const query = serializeDashboardViewState(defaultDashboardViewState(tab)).toString();

	return query ? `?${query}` : '';
};
