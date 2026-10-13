import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import type { InfiniteData } from '@tanstack/react-query';
import type Echo from 'laravel-echo';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { articleKeys } from '@/api/articles/keys';
import { getPendingArticlesQueryKey } from '@/api/articles/moderation';
import { getInfiniteCataloguesQueryKey } from '@/api/catalogues/hooks/useInfiniteCatalogues';
import { dashboardKeys } from '@/api/dashboard/keys';
import type {
	ArticleListResource,
	ArticleModerationListResource,
	ArticleResource,
	CatalogueListResource,
	CatalogueResource,
} from '@/api/generated/model';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import {
	everyStateArticles,
	hostileArticles,
	makeProcessing,
	manyArticles,
} from '@/components/features/dashboard/dashboardFixtures';
import { hostileCatalogues, knownLists } from '@/components/features/dashboard/dashboardListFixtures';
import { reviewQueue } from '@/components/features/dashboard/dashboardReviewFixtures';
import type { QuerySeed } from '@/storybook/decorators';
import type { User } from '@/types';
import { dashboardArticleFilters } from './DashboardArticlesPanel';
import { dashboardCatalogueFilters } from './DashboardCataloguesPanel';
import { DASHBOARD_PER_PAGE } from './DashboardListSection';
import { defaultDashboardViewState } from './dashboardSearchParams';
import Dashboard from './index';

/*
 * The whole Dashboard route on seeded data. The cache (`parameters.seeds`) holds the default view of
 * each tab (staleTime: Infinity), so nothing fetches; a view that was not seeded (another filter)
 * goes to the network and, without a backend, shows the real error state. A fake Echo instance
 * captures the owner-channel listener so a story can push a processing update into a row.
 */

const owner = {
	id: 7,
	uuid: 'owner-uuid',
	name: 'Hanako',
	email: 'hanako@example.com',
	roles: [],
	isAdmin: false,
} as User;
const admin = { ...owner, id: 1, uuid: 'admin-uuid', isAdmin: true } as User;

/** Splits rows into API pages of 25, with the pagination the backend would send. */
const toPages = <T,>(items: T[]) => {
	const total = items.length;
	const lastPage = Math.max(1, Math.ceil(total / DASHBOARD_PER_PAGE));

	return Array.from({ length: lastPage }, (_, index) => ({
		items: items.slice(index * DASHBOARD_PER_PAGE, (index + 1) * DASHBOARD_PER_PAGE),
		pagination: {
			page: index + 1,
			per_page: DASHBOARD_PER_PAGE,
			total,
			last_page: lastPage,
			has_more: index + 1 < lastPage,
		},
	}));
};

/** Every loaded page at once, as if Load more had been pressed until the end. */
const infinite = <P,>(pages: P[]): InfiniteData<P> => ({ pages, pageParams: pages.map((_, index) => index + 1) });

const firstPageOnly = <P,>(pages: P[]): InfiniteData<P> => infinite(pages.slice(0, 1));

interface Seed {
	user: User;
	articles?: ArticleResource[];
	catalogues?: CatalogueResource[];
	loadEverything?: boolean;
}

/** The cache for the default view of each tab, for `user`. */
const dashboardSeeds = ({ user, articles = [], catalogues = [], loadEverything = false }: Seed): QuerySeed[] => {
	const pick = loadEverything ? infinite : firstPageOnly;

	return [
		{
			queryKey: articleKeys.list(dashboardArticleFilters(user.uuid, '', 'all')),
			data: pick(
				toPages(articles).map(
					(page) =>
						({
							...page,
							facets: [],
							applied: { q: null, filters: {}, sort: '-created_at' },
						}) as unknown as ArticleListResource,
				),
			),
		},
		{
			queryKey: getInfiniteCataloguesQueryKey(
				dashboardCatalogueFilters(user.uuid, defaultDashboardViewState('lists')),
			),
			data: pick(toPages(catalogues) as unknown as CatalogueListResource[]),
		},
		{
			queryKey: getPendingArticlesQueryKey({ per_page: DASHBOARD_PER_PAGE }),
			data: pick(toPages(reviewQueue) as unknown as ArticleModerationListResource[]),
		},
		{
			queryKey: dashboardKeys.counts(user.uuid),
			data: {
				articles: articles.length,
				lists: catalogues.length,
				awaitingReview: articles.filter((article) => article.status === 0 || article.status === 2).length,
			},
		},
	];
};

const defaultSeed: Seed = {
	user: owner,
	articles: [...hostileArticles, ...everyStateArticles],
	catalogues: [...hostileCatalogues, ...knownLists],
};

/** A connected socket whose owner-channel listener the story can call. */
const fakeSocket = () => {
	const listeners: Array<(payload: unknown) => void> = [];
	const channel = {
		listen: (_event: string, callback: (payload: unknown) => void) => {
			listeners.push(callback);
			return channel;
		},
		stopListening: () => channel,
		subscribed: () => channel,
		error: () => channel,
	};
	const echo = {
		private: () => channel,
		leave: () => undefined,
		leaveChannel: () => undefined,
		connector: { pusher: { connection: { bind: () => undefined, unbind: () => undefined, state: 'connected' } } },
	};

	return {
		context: { echo: echo as unknown as Echo<'reverb'>, generation: 1 },
		emit: (payload: unknown) => listeners.forEach((listener) => listener(payload)),
		reset: () => {
			listeners.length = 0;
		},
	};
};

const live = fakeSocket();

/** Story-only: one button that pushes "processing finished" for the second article. */
const LiveUpdateButton: React.FC<{ onEmit: () => void }> = ({ onEmit }) => (
	<button type="button" onClick={onEmit} style={{ margin: 8 }}>
		Story: finish processing of 記事 1-2
	</button>
);

const target = everyStateArticles[1];

/** The dashboard at `tab`, as the router would open it. */
const atTab = (tab: 'lists' | 'review') => ({ initialEntries: [`/dashboard?tab=${tab}`] });

const meta = {
	title: 'Pages/Dashboard',
	component: Dashboard,
	parameters: {
		layout: 'fullscreen',
		auth: { user: owner },
		socket: { ...live.context, isConfigured: true, connectionStatus: 'connected', isConnected: true },
		router: { initialEntries: ['/dashboard'] },
		seeds: dashboardSeeds(defaultSeed),
	},
	beforeEach: () => live.reset(),
	render: () => (
		<>
			<LiveUpdateButton
				onEmit={() => live.emit({ ...makeProcessing(ProcessingStatus.completed, target.uuid), sequence: 2 })}
			/>
			<Dashboard />
		</>
	),
} satisfies Meta<typeof Dashboard>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Every approval × processing state; the story button sends a live "completed" to row 記事 1-2. */
export const Articles: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const table = within(await canvas.findByRole('table', { name: 'Your articles' }));
		const row = () => table.getByRole('link', { name: '記事 1-2' }).closest('tr') as HTMLElement;

		await expect(within(row()).getAllByText('Processing').length).toBeGreaterThan(0);
		await userEvent.click(canvas.getByRole('button', { name: /finish processing/ }));
		await waitFor(() => expect(within(row()).queryAllByText('Processing')).toHaveLength(0));
		await expect(within(row()).getByText('Nothing in progress')).toBeInTheDocument();
	},
};

export const Lists: Story = { parameters: { router: atTab('lists') } };

/** A brand-new user: no articles, and only the four built-in Known lists. */
export const NewUser: Story = {
	parameters: { seeds: dashboardSeeds({ user: owner, articles: [], catalogues: knownLists }) },
};

export const NewUserLists: Story = { parameters: { ...NewUser.parameters, router: atTab('lists') } };

/** 500 articles, all loaded: the sticky header holds and nothing overflows. */
export const FiveHundredArticles: Story = {
	// Axe over 500 rows outruns the test timeout; the same rows are checked at normal size above.
	parameters: {
		seeds: dashboardSeeds({ ...defaultSeed, articles: manyArticles(500), loadEverything: true }),
		a11y: { test: 'off' },
	},
};

/** Admins get the review queue as a third section. */
export const AdminReview: Story = {
	parameters: {
		auth: { user: admin },
		router: atTab('review'),
		seeds: dashboardSeeds({ ...defaultSeed, user: admin }),
	},
};
