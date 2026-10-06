import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, userEvent, waitFor, within } from '@storybook/test';
import { QueryClient, QueryClientProvider, type InfiniteData } from '@tanstack/react-query';
import type Echo from 'laravel-echo';
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
import { AuthContext } from '@/providers/contexts/auth-provider';
import { DEFAULT_SOCKET_CONTEXT, SocketContext } from '@/providers/contexts/socket-provider';
import type { User } from '@/types';
import { dashboardArticleFilters } from './DashboardArticlesPanel';
import { dashboardCatalogueFilters } from './DashboardCataloguesPanel';
import { DASHBOARD_PER_PAGE } from './DashboardListSection';
import { defaultDashboardViewState } from './dashboardSearchParams';
import Dashboard from './index';

/*
 * The whole Dashboard route on seeded data. The QueryClient is pre-filled for the default view of
 * each tab (staleTime: Infinity), so nothing fetches; a view that was not seeded (another filter)
 * goes to the network and, without a backend, shows the real error state. A fake Echo instance
 * captures the owner-channel listener so a story can push a processing update into a row.
 */

type AuthValue = NonNullable<React.ComponentProps<typeof AuthContext.Provider>['value']>;

const noop = async () => undefined;

const signedIn = (user: User): AuthValue => ({
	user,
	isAuthenticated: true,
	isLoading: false,
	sessionExpired: false,
	token: 'story-token',
	login: noop,
	register: noop,
	logout: noop,
	clearSessionExpired: () => undefined,
});

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

const seededClient = ({ user, articles = [], catalogues = [], loadEverything = false }: Seed) => {
	const client = new QueryClient({
		defaultOptions: { queries: { staleTime: Infinity, retry: false, refetchOnWindowFocus: false } },
	});
	const pick = loadEverything ? infinite : firstPageOnly;

	client.setQueryData(
		articleKeys.list(dashboardArticleFilters(user.uuid, '', 'all')),
		pick(
			toPages(articles).map(
				(page) =>
					({
						...page,
						facets: [],
						applied: { q: null, filters: {}, sort: '-created_at' },
					}) as unknown as ArticleListResource,
			),
		),
	);
	client.setQueryData(
		getInfiniteCataloguesQueryKey(dashboardCatalogueFilters(user.uuid, defaultDashboardViewState('lists'))),
		pick(toPages(catalogues) as unknown as CatalogueListResource[]),
	);
	client.setQueryData(
		getPendingArticlesQueryKey({ per_page: DASHBOARD_PER_PAGE }),
		pick(toPages(reviewQueue) as unknown as ArticleModerationListResource[]),
	);
	client.setQueryData(dashboardKeys.counts(user.uuid), {
		articles: articles.length,
		lists: catalogues.length,
		awaitingReview: articles.filter((article) => article.status === 0 || article.status === 2).length,
	});

	return client;
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
		context: {
			...DEFAULT_SOCKET_CONTEXT,
			echo: echo as unknown as Echo<'reverb'>,
			generation: 1,
			isConfigured: true,
			isConnected: true,
			connectionStatus: 'connected' as const,
			hasAttemptedConnection: true,
		},
		emit: (payload: unknown) => listeners.forEach((listener) => listener(payload)),
	};
};

interface DashboardStoryArgs extends Seed {
	tab?: 'articles' | 'lists' | 'review';
}

/** Story-only: one button that pushes "processing finished" for the second article. */
const LiveUpdateButton: React.FC<{ onEmit: () => void }> = ({ onEmit }) => (
	<button type="button" onClick={onEmit} style={{ margin: 8 }}>
		Story: finish processing of 記事 1-2
	</button>
);

const DashboardStory: React.FC<DashboardStoryArgs> = (args) => {
	const [client] = React.useState(() => seededClient(args));
	const [socket] = React.useState(fakeSocket);
	const target = everyStateArticles[1];

	return (
		<QueryClientProvider client={client}>
			<AuthContext.Provider value={signedIn(args.user)}>
				<SocketContext.Provider value={socket.context}>
					<LiveUpdateButton
						onEmit={() =>
							socket.emit({
								...makeProcessing(ProcessingStatus.completed, target.uuid),
								sequence: 2,
							})
						}
					/>
					<Dashboard />
				</SocketContext.Provider>
			</AuthContext.Provider>
		</QueryClientProvider>
	);
};

/** Puts the tab in the iframe URL before the router reads it. */
const openTab = (tab: DashboardStoryArgs['tab']) => () => {
	const url = new URL(window.location.href);

	for (const key of ['tab', 'q', 'status', 'type', 'sort']) {
		url.searchParams.delete(key);
	}

	if (tab && tab !== 'articles') {
		url.searchParams.set('tab', tab);
	}

	window.history.replaceState(window.history.state, '', url);
};

const meta = {
	title: 'Routes/Dashboard',
	component: DashboardStory,
	parameters: { layout: 'fullscreen' },
	args: {
		user: owner,
		articles: [...hostileArticles, ...everyStateArticles],
		catalogues: [...hostileCatalogues, ...knownLists],
	},
	beforeEach: openTab('articles'),
} satisfies Meta<typeof DashboardStory>;

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

export const Lists: Story = { beforeEach: openTab('lists') };

/** A brand-new user: no articles, and only the four built-in Known lists. */
export const NewUser: Story = { args: { articles: [], catalogues: knownLists } };

export const NewUserLists: Story = { ...NewUser, beforeEach: openTab('lists') };

/** 500 articles, all loaded: the sticky header holds and nothing overflows. */
export const FiveHundredArticles: Story = { args: { articles: manyArticles(500), loadEverything: true } };

/** Admins get the review queue as a third section. */
export const AdminReview: Story = { args: { user: admin }, beforeEach: openTab('review') };
