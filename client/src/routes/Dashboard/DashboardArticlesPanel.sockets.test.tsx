/**
 * @vitest-environment jsdom
 */
import type { ReactNode } from 'react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import type Echo from 'laravel-echo';
import { describe, expect, it, vi } from 'vitest';
import { usePendingArticles } from '@/api/articles/moderation';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import { DEFAULT_SOCKET_CONTEXT, SocketContext } from '@/providers/contexts/socket-provider';
import { renderWithAct } from '@/test/renderWithAct';
import type { User } from '@/types';
import DashboardArticlesPanel from './DashboardArticlesPanel';
import { DASHBOARD_TYPES } from './dashboard.constants';

vi.mock('@/api/articles/moderation', () => ({
	usePendingArticles: vi.fn(),
}));

const pendingArticles = Array.from({ length: 10 }, (_, index) => ({
	uuid: `article-${index}`,
	title_jp: `記事 ${index}`,
	processing_status: {
		id: index,
		entity_id: `article-${index}`,
		type: 'article_content_processing',
		status: ProcessingStatus.pending,
		attempt: 0,
		metadata: {},
		created_at: '2026-09-20T10:00:00+00:00',
		updated_at: '2026-09-20T10:00:00+00:00',
	},
}));

vi.mock('@/api/articles/hooks/useInfiniteArticles', () => ({
	useInfiniteArticles: () => ({
		articles: pendingArticles,
		total: pendingArticles.length,
		error: null,
		fetchNextPage: vi.fn(),
		hasNextPage: false,
		isFetchingNextPage: false,
		status: 'success',
	}),
}));

vi.mock('./SearchBarDashboard', () => ({ default: () => <div>Search bar</div> }));
vi.mock('@/components/features/dashboard/DashboardArticleItem', () => ({ default: () => <div>item</div> }));
vi.mock('@/components/shared/Link', () => ({
	Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
}));
vi.mock('@/components/shared/Button', () => ({
	Button: ({ children }: { children: ReactNode }) => <button type="button">{children}</button>,
}));
vi.mock('@/components/shared/Chip', () => ({
	Chip: ({ children }: { children: ReactNode }) => <span>{children}</span>,
}));
vi.mock('@/components/shared/Icon', () => ({ Icon: ({ name }: { name: string }) => <span>{name}</span> }));

const owner = { id: 7, uuid: 'owner-uuid', isAdmin: false } as User;

/**
 * Issue #263: every private channel costs one POST /api/broadcasting/auth. Ten pending
 * articles on the owner dashboard must cost one, not ten.
 */
describe('DashboardArticlesPanel socket usage', () => {
	it('opens exactly one private channel for ten pending articles', async () => {
		vi.mocked(usePendingArticles).mockReturnValue({ pendingArticles: [], total: 0, isPending: false } as never);
		const channel = { listen: vi.fn(), stopListening: vi.fn(), subscribed: vi.fn(), error: vi.fn() };
		const echo = {
			private: vi.fn(() => channel),
			leave: vi.fn(),
			leaveChannel: vi.fn(),
			connector: { pusher: { connection: { bind: vi.fn(), unbind: vi.fn(), state: 'connected' } } },
		};

		const rendered = await renderWithAct(
			<QueryClientProvider client={new QueryClient()}>
				<SocketContext.Provider
					value={{ ...DEFAULT_SOCKET_CONTEXT, echo: echo as unknown as Echo<'reverb'>, generation: 1 }}
				>
					<DashboardArticlesPanel
						dashboardView={DASHBOARD_TYPES.COMMON_USER}
						isAuthenticated
						currentUser={owner}
						onToggleDashboardView={vi.fn()}
					/>
				</SocketContext.Provider>
			</QueryClientProvider>,
		);

		expect(echo.private).toHaveBeenCalledTimes(1);
		expect(echo.private).toHaveBeenCalledWith('App.User.owner-uuid');

		await rendered.unmount();
	});
});
