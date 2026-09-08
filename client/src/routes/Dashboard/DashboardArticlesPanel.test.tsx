import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { usePendingArticles } from '@/api/articles/moderation';
import type { ArticleModerationItemResource } from '@/api/generated/model/articleModerationItemResource';
import type { User } from '@/types';
import DashboardArticlesPanel from './DashboardArticlesPanel';
import { DASHBOARD_TYPES } from './dashboard.constants';

vi.mock('@/api/articles/moderation', () => ({
	usePendingArticles: vi.fn(),
}));

vi.mock('@/api/articles/hooks/useInfiniteArticles', () => ({
	useInfiniteArticles: () => ({
		articles: [],
		total: 0,
		error: null,
		fetchNextPage: vi.fn(),
		hasNextPage: false,
		isFetchingNextPage: false,
		status: 'success',
	}),
}));

vi.mock('@/api/articles/hooks/useArticleSubscription', () => ({
	useArticleSubscription: vi.fn(),
}));

vi.mock('./SearchBarDashboard', () => ({
	default: () => <div>Search bar</div>,
}));

vi.mock('@/components/features/dashboard/DashboardArticleItem', () => ({
	default: () => <div>Dashboard article item</div>,
}));

vi.mock('@/components/shared/Link', () => ({
	Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
}));

vi.mock('@/components/shared/Button', () => ({
	Button: ({ children }: { children: ReactNode }) => <button type="button">{children}</button>,
}));

vi.mock('@/components/shared/Chip', () => ({
	Chip: ({ children }: { children: ReactNode }) => <span>{children}</span>,
}));

vi.mock('@/components/shared/Icon', () => ({
	Icon: ({ name }: { name: string }) => <span>{name}</span>,
}));

const adminUser = { id: 1, uuid: 'admin-uuid', isAdmin: true } as User;

const createPendingArticle = (
	overrides: Partial<ArticleModerationItemResource> = {},
): ArticleModerationItemResource => ({
	uuid: 'a51a0f0e-1a4d-4a1e-9b0f-6f6c1c6a2f11',
	title_jp: '審査待ちの記事',
	status: 0,
	status_label: 'Pending',
	hashtags: [{ id: 4, content: 'grammar', created_at: null, updated_at: null }],
	created_at: '2026-05-04T09:00:00+00:00',
	...overrides,
});

const mockPendingArticles = (overrides: Record<string, unknown> = {}) => {
	vi.mocked(usePendingArticles).mockReturnValue({
		pendingArticles: [],
		total: 0,
		isPending: false,
		isError: false,
		error: null,
		hasNextPage: false,
		isFetchingNextPage: false,
		fetchNextPage: vi.fn(),
		...overrides,
	} as never);
};

const renderAdminPanel = () =>
	renderToStaticMarkup(
		<DashboardArticlesPanel
			dashboardView={DASHBOARD_TYPES.ADMIN}
			isAuthenticated
			currentUser={adminUser}
			onToggleDashboardView={vi.fn()}
		/>,
	);

describe('DashboardArticlesPanel admin moderation queue', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		mockPendingArticles();
	});

	it('renders the loading state while the pending queue is fetching', () => {
		mockPendingArticles({ isPending: true });

		expect(renderAdminPanel()).toContain('alt="Loading pending articles..."');
	});

	it('surfaces the pending queue error message', () => {
		mockPendingArticles({ isError: true, error: new Error('Moderation queue unavailable') });

		const html = renderAdminPanel();

		expect(html).toContain('alert-danger');
		expect(html).toContain('Moderation queue unavailable');
	});

	it('renders the empty state when nothing awaits review', () => {
		expect(renderAdminPanel()).toContain('There are no articles to review.');
	});

	it('links queue rows by UUID and shows the generated status label', () => {
		mockPendingArticles({ pendingArticles: [createPendingArticle()] });

		const html = renderAdminPanel();

		expect(html).toContain('href="/articles/a51a0f0e-1a4d-4a1e-9b0f-6f6c1c6a2f11"');
		expect(html).toContain('審査待ちの記事');
		expect(html).toContain('<strong>Pending</strong>');
		expect(html).toContain('grammar');
		expect(html).not.toContain('/article/');
	});

	it('offers Load More only while the queue has further pages', () => {
		mockPendingArticles({ pendingArticles: [createPendingArticle()], hasNextPage: true });
		expect(renderAdminPanel()).toContain('Load More');

		mockPendingArticles({ pendingArticles: [createPendingArticle()], hasNextPage: false });
		const html = renderAdminPanel();
		expect(html).not.toContain('Load More');
		expect(html).toContain('No more results');
	});

	it('only fetches the queue for authenticated admins', () => {
		renderAdminPanel();
		expect(usePendingArticles).toHaveBeenCalledWith({ enabled: true });

		vi.clearAllMocks();
		mockPendingArticles();
		renderToStaticMarkup(
			<DashboardArticlesPanel
				dashboardView={DASHBOARD_TYPES.ADMIN}
				isAuthenticated
				currentUser={{ id: 2, uuid: 'user-uuid', isAdmin: false } as User}
				onToggleDashboardView={vi.fn()}
			/>,
		);
		expect(usePendingArticles).toHaveBeenCalledWith({ enabled: false });
	});
});
