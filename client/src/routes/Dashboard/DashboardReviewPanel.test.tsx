// @vitest-environment jsdom
import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { usePendingArticles } from '@/api/articles/moderation';
import type { ArticleModerationItemResource } from '@/api/generated/model/articleModerationItemResource';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import DashboardReviewPanel from './DashboardReviewPanel';

vi.mock('@/api/articles/moderation', () => ({
	usePendingArticles: vi.fn(),
}));

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
		refetch: vi.fn(),
		...overrides,
	} as never);
};

const panel = (enabled = true) => (
	<MemoryRouter>
		<DashboardReviewPanel enabled={enabled} />
	</MemoryRouter>
);

const render = (enabled = true) => renderToStaticMarkup(panel(enabled));

/** The admin cases of the old DashboardArticlesPanel test, against the Review tab. */
describe('DashboardReviewPanel', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		mockPendingArticles();
	});

	it('renders skeleton rows while the queue is fetching', () => {
		mockPendingArticles({ isPending: true });

		const html = render();

		expect(html).toContain('aria-busy="true"');
		expect(html).not.toContain('Showing');
	});

	it('shows a fixed message and a retry when the queue fails, never the raw error', async () => {
		const refetch = vi.fn();
		mockPendingArticles({ isError: true, error: new Error('Moderation queue unavailable'), refetch });

		const view = await renderWithAct(panel());

		expect(view.container.textContent).toContain('The review queue could not be loaded.');
		expect(view.container.textContent).not.toContain('Moderation queue unavailable');

		await view.flush(() => requireElement<HTMLButtonElement>(view.container, 'button').click());
		expect(refetch).toHaveBeenCalledTimes(1);
		await view.unmount();
	});

	it('renders the empty panel when nothing awaits review', () => {
		const html = render();

		expect(html).toContain('Nothing awaits review');
		expect(html).toContain('0 articles awaiting review');
	});

	it('links queue rows by UUID and shows the status with text', () => {
		mockPendingArticles({ pendingArticles: [createPendingArticle()], total: 1 });

		const html = render();

		expect(html).toContain('href="/articles/a51a0f0e-1a4d-4a1e-9b0f-6f6c1c6a2f11"');
		expect(html).toContain('審査待ちの記事');
		expect(html).toContain('>Pending<');
		expect(html).toContain('grammar');
		expect(html).not.toContain('/article/');
	});

	it('offers Load more only while the queue has further pages', () => {
		mockPendingArticles({ pendingArticles: [createPendingArticle()], total: 40, hasNextPage: true });
		expect(render()).toContain('Load more');

		mockPendingArticles({ pendingArticles: [createPendingArticle()], total: 1, hasNextPage: false });
		const html = render();
		expect(html).not.toContain('Load more');
		expect(html).toContain('No more results');
	});

	it('only fetches the queue when enabled', () => {
		render(true);
		expect(usePendingArticles).toHaveBeenCalledWith(expect.objectContaining({ enabled: true }));

		vi.clearAllMocks();
		mockPendingArticles();
		render(false);
		expect(usePendingArticles).toHaveBeenCalledWith(expect.objectContaining({ enabled: false }));
	});
});
