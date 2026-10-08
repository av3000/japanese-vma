import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { CatalogueForItem } from '@/api/catalogues/cataloguesForItem';
import { articleExportKanjisPdf, articleExportWordsPdf } from '@/api/generated/article/article';
import { catalogueAddItem, catalogueRemoveItem } from '@/api/generated/catalogue/catalogue';
import { downloadFile } from '@/helpers/downloadFile';
import ArticleContent, { articleEditHref } from './index';

const fetchCataloguesForItemMock = vi.fn();
const capturedModalProps: Array<{
	lists: CatalogueForItem[];
	loadingListIds: number[];
	onListAction: (list: CatalogueForItem, action: 'add' | 'remove') => Promise<void>;
}> = [];
const capturedPdfModalProps: Array<{
	onDownload: (type: 'kanji' | 'words') => Promise<void>;
	isDownloadEnabled: boolean;
}> = [];
const capturedReviewModalProps: Array<{
	status: number;
	onStatusChange: (nextStatus: number) => void;
	onSave: () => void;
	isProcessing: boolean;
}> = [];
const capturedStatusMutationArgs: Array<[string, { onSuccess?: () => void; onError?: () => void }]> = [];
const statusMutateMock = vi.fn();
const modalCloseMocks: Record<string, ReturnType<typeof vi.fn>> = {};
let statusMutationIsPending = false;
const navigateMock = vi.fn();
const likeMutateMock = vi.fn();
let likeIsToggling = false;
let isAuthenticatedMock = true;
let currentUserMock: { id: number; isAdmin: boolean } = { id: 7, isAdmin: false };
const capturedLikeButtonProps: Array<{ onClick?: () => void; disabled?: boolean; 'aria-pressed'?: boolean }> = [];

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useNavigate: () => navigateMock,
		useSearchParams: () => [new URLSearchParams(), vi.fn()],
	};
});

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
	return {
		...actual,
		useMutation: vi.fn((options: any) => ({
			mutate: vi.fn((variables: unknown) => {
				void options.mutationFn(variables);
			}),
			mutateAsync: vi.fn(async (variables: unknown) => {
				const result = await options.mutationFn(variables);
				await options.onSuccess?.(result, variables, undefined);
				return result;
			}),
			isPending: false,
		})),
	};
});

vi.mock('@/api/generated/catalogue/catalogue', () => ({
	catalogueAddItem: vi.fn(),
	catalogueRemoveItem: vi.fn(),
}));

// Only the network read is stubbed: the widget's add/remove path must reach the real
// helper so the assertions below observe the generated catalogue endpoints.
vi.mock('@/api/catalogues/cataloguesForItem', async () => {
	const actual = await vi.importActual<typeof import('@/api/catalogues/cataloguesForItem')>(
		'@/api/catalogues/cataloguesForItem',
	);
	return {
		...actual,
		fetchCataloguesForItem: (...args: unknown[]) => fetchCataloguesForItemMock(...args),
	};
});

vi.mock('@/api/articles/details', () => ({
	useLikeArticleMutation: () => ({
		mutate: likeMutateMock,
		isPending: false,
		isTogglingInstance: () => likeIsToggling,
	}),
}));

vi.mock('@/api/articles/hooks/useArticleSubscription', () => ({
	useArticleSubscription: vi.fn(),
}));

vi.mock('@/api/articles/readingStats', async () => {
	const actual = await vi.importActual<typeof import('@/api/articles/readingStats')>('@/api/articles/readingStats');
	return {
		...actual,
		useArticleReadingStats: (article: Parameters<typeof actual.useArticleReadingStats>[0]) => ({
			kanji: actual.isProcessingRunning(article) ? null : actual.kanjiCountOf(article.jlpt_levels),
			words: actual.isProcessingRunning(article) ? null : 5,
		}),
	};
});

vi.mock('@/api/articles/moderation', () => ({
	useArticleStatusMutation: (
		articleUuid: string,
		callbacks: { onSuccess?: () => void; onError?: () => void } = {},
	) => {
		capturedStatusMutationArgs.push([articleUuid, callbacks]);
		return { mutate: statusMutateMock, isPending: statusMutationIsPending };
	},
}));

vi.mock('@/helpers/downloadFile', async () => {
	const actual = await vi.importActual<typeof import('@/helpers/downloadFile')>('@/helpers/downloadFile');
	return {
		...actual,
		downloadFile: vi.fn(),
	};
});

vi.mock('@/api/generated/article/article', () => ({
	articleDestroy: vi.fn(),
	articleExportKanjisPdf: vi.fn(),
	articleExportWordsPdf: vi.fn(),
}));

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({
		user: currentUserMock,
		isAuthenticated: isAuthenticatedMock,
	}),
}));

vi.mock('@/hooks/useModal', () => ({
	useModal: (dialogRef: { current: null }, options: { id: string; onClose?: () => void }) => {
		modalCloseMocks[options.id] ??= vi.fn();

		return {
			id: options.id,
			dialogRef,
			isOpen: false,
			isRendered: true,
			open: vi.fn(),
			close: options.onClose ?? modalCloseMocks[options.id],
		};
	},
}));

vi.mock('@/components/features/catalogues/CatalogueBookmarkModal', () => ({
	CatalogueBookmarkModal: (props: any) => {
		capturedModalProps.push(props);
		return <div>Bookmark modal</div>;
	},
}));

vi.mock('@/components/features/DeleteInstanceModal', () => ({
	DeleteInstanceModal: () => <div>Delete modal</div>,
}));

vi.mock('@/components/features/ProcessingStatusAlert', () => ({
	default: () => <div>Processing status</div>,
}));

vi.mock('@/components/features/articles/ArticlePdfModal', () => ({
	ArticlePdfModal: (props: any) => {
		capturedPdfModalProps.push(props);
		return <div>Article pdf modal</div>;
	},
}));

vi.mock('@/components/features/articles/ArticleReviewModal', () => ({
	ArticleReviewModal: (props: any) => {
		capturedReviewModalProps.push(props);
		return <div>Article review modal</div>;
	},
}));

vi.mock('@/components/features/articles/ArticleAttachments', () => ({
	ArticleAttachments: ({ articleUuid }: { articleUuid: string }) => <div data-attachments={articleUuid} />,
}));

vi.mock('@/components/features/comment/CommentsBlock', () => ({
	default: () => <div>Comments</div>,
}));

vi.mock('@/components/shared/Button', () => ({
	Button: ({
		children,
		onClick,
		disabled,
		to,
		...rest
	}: {
		children: ReactNode;
		onClick?: () => void;
		disabled?: boolean;
		to?: string;
		'aria-label'?: string;
		'aria-pressed'?: boolean;
	}) => {
		if (rest['aria-pressed'] !== undefined) {
			capturedLikeButtonProps.push({ onClick, disabled, 'aria-pressed': rest['aria-pressed'] });
		}

		if (to) return <a href={to}>{children}</a>;

		return (
			<button type="button" disabled={disabled} aria-pressed={rest['aria-pressed']}>
				{children}
			</button>
		);
	},
}));

vi.mock('@/components/shared/Chip', () => ({
	Chip: ({ children }: { children: ReactNode }) => <span>{children}</span>,
}));

vi.mock('@/components/shared/Icon', () => ({
	Icon: ({ name }: { name: string }) => <span>{name}</span>,
}));

vi.mock('@/components/shared/StatusPill', () => ({
	articleStatusPill: () => ({ tone: 'neutral', label: 'Article status' }),
	StatusPill: ({ label }: { label: string }) => <div>{label}</div>,
}));

vi.mock('@/components/ui/badge', () => ({
	Badge: ({ children }: { children: ReactNode }) => <span>{children}</span>,
}));

vi.mock('../ArticleEditModal', () => ({
	default: () => <div>Article edit modal</div>,
}));

const createArticle = (engagementOverrides: Record<string, unknown> = {}) =>
	({
		id: 321,
		uuid: 'article-uuid',
		title_jp: 'Study Article',
		content_jp: 'Body',
		status: 1,
		jlpt_levels: { n1: 0, n2: 2, n3: 9, n4: 3, n5: 6, uncommon: 1 },
		formattedDate: '2026-05-04',
		displayName: 'Aki',
		publicity: 1,
		author: {
			id: 7,
			uuid: 'author-uuid',
			name: 'Aki',
		},
		engagement: {
			views_count: 8,
			likes_count: 3,
			is_liked_by_viewer: false,
			...engagementOverrides,
		},
		hashtags: [],
		processing_status: {
			type: 'article_content_processing',
			status: 'completed',
		},
	}) as any;

describe('ArticleContent', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		capturedModalProps.length = 0;
		capturedPdfModalProps.length = 0;
		capturedReviewModalProps.length = 0;
		capturedStatusMutationArgs.length = 0;
		capturedLikeButtonProps.length = 0;
		statusMutationIsPending = false;
		likeIsToggling = false;
		isAuthenticatedMock = true;
		currentUserMock = { id: 7, isAdmin: false };
		fetchCataloguesForItemMock.mockResolvedValue(cataloguesForItemLists);
		vi.mocked(catalogueAddItem).mockResolvedValue([] as never);
		vi.mocked(catalogueRemoveItem).mockResolvedValue(204 as never);
		vi.mocked(articleExportKanjisPdf).mockResolvedValue('%PDF-1.7' as never);
		vi.mocked(articleExportWordsPdf).mockResolvedValue('%PDF-1.7' as never);
	});

	const cataloguesForItemLists: CatalogueForItem[] = [
		{
			id: 9,
			uuid: 'd453be67-1519-43e2-94ab-af85b79aeb31',
			title: 'My catalogue',
			type: 5,
			type_label: 'Radicals',
			publicity: 0,
			contains_item: false,
		},
	];

	it('adds article bookmarks through the v1 catalogue item endpoint', async () => {
		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		await capturedModalProps[0].onListAction(cataloguesForItemLists[0], 'add');

		expect(catalogueAddItem).toHaveBeenCalledWith('d453be67-1519-43e2-94ab-af85b79aeb31', { item_id: 321 });
		expect(catalogueRemoveItem).not.toHaveBeenCalled();
	});

	it('removes article bookmarks through the v1 catalogue item endpoint', async () => {
		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		await capturedModalProps[0].onListAction(
			{
				...cataloguesForItemLists[0],
				contains_item: true,
			},
			'remove',
		);

		expect(catalogueRemoveItem).toHaveBeenCalledWith('d453be67-1519-43e2-94ab-af85b79aeb31', 321);
		expect(catalogueAddItem).not.toHaveBeenCalled();
	});

	it('shows the JLPT level bar in the reading facts', () => {
		const html = renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		expect(html).toContain('aria-label="Mostly N3: N5 6, N4 3, N3 9, N2 2, uncommon 1"');
	});

	it('shows no JLPT level bar while every count is 0', () => {
		const article = createArticle();
		article.jlpt_levels = { n1: 0, n2: 0, n3: 0, n4: 0, n5: 0, uncommon: 0 };

		expect(renderToStaticMarkup(<ArticleContent article={article} />)).not.toContain('role="img"');
	});

	it.each(['pending', 'processing', 'failed', 'superseded'])(
		'does not offer PDF downloads while the consolidated status is %s',
		(status) => {
			const article = createArticle();
			article.processing_status = { type: 'article_content_processing', status };

			renderToStaticMarkup(<ArticleContent article={article} />);

			expect(capturedPdfModalProps[0].isDownloadEnabled).toBe(false);
		},
	);

	it('offers both PDF downloads once the consolidated status is completed', () => {
		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		expect(capturedPdfModalProps[0].isDownloadEnabled).toBe(true);
	});

	it('downloads article kanji pdf through the generated v1 article endpoint', async () => {
		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		await capturedPdfModalProps[0].onDownload('kanji');

		expect(articleExportKanjisPdf).toHaveBeenCalledWith('article-uuid', { responseType: 'blob' });
		expect(articleExportWordsPdf).not.toHaveBeenCalled();
		expect(downloadFile).toHaveBeenCalledWith('Study Article.pdf', expect.any(Blob));
	});

	it('downloads article words pdf through the generated v1 article endpoint', async () => {
		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		await capturedPdfModalProps[0].onDownload('words');

		expect(articleExportWordsPdf).toHaveBeenCalledWith('article-uuid', { responseType: 'blob' });
		expect(articleExportKanjisPdf).not.toHaveBeenCalled();
		expect(downloadFile).toHaveBeenCalledWith('Study Article.pdf', expect.any(Blob));
	});

	it('moderates through the UUID-keyed status seam rather than numeric identity', () => {
		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		expect(capturedStatusMutationArgs[0][0]).toBe('article-uuid');

		capturedReviewModalProps[0].onSave();

		expect(statusMutateMock).toHaveBeenCalledWith(capturedReviewModalProps[0].status);
		expect(capturedReviewModalProps[0].status).toBe(createArticle().status);
	});

	it('closes the review modal once the status mutation succeeds', () => {
		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		expect(modalCloseMocks['article-review-modal']).not.toHaveBeenCalled();

		capturedStatusMutationArgs[0][1].onSuccess?.();

		expect(modalCloseMocks['article-review-modal']).toHaveBeenCalled();
	});

	it('keeps the review modal open and writes no status when the mutation fails', () => {
		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		capturedStatusMutationArgs[0][1].onError?.();

		expect(modalCloseMocks['article-review-modal']).not.toHaveBeenCalled();
	});

	it('blocks a duplicate submit while the status mutation is in flight', () => {
		statusMutationIsPending = true;

		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		expect(capturedReviewModalProps[0].isProcessing).toBe(true);
	});

	it('renders the unfilled like icon for an article the viewer has not liked', () => {
		const html = renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		expect(html).toContain('thumbsUpRegular');
		expect(capturedLikeButtonProps[0]['aria-pressed']).toBe(false);
	});

	it('renders the filled like icon for an article the viewer already liked', () => {
		const html = renderToStaticMarkup(<ArticleContent article={createArticle({ is_liked_by_viewer: true })} />);

		expect(html).toContain('thumbsUpSolid');
		expect(capturedLikeButtonProps[0]['aria-pressed']).toBe(true);
	});

	it('likes through the loaded numeric article id rather than the uuid route parameter', () => {
		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		capturedLikeButtonProps[0].onClick?.();

		expect(likeMutateMock).toHaveBeenCalledWith(321);
	});

	it('sends an anonymous reader to login instead of a like the endpoint would reject', () => {
		isAuthenticatedMock = false;
		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		capturedLikeButtonProps[0].onClick?.();

		expect(likeMutateMock).not.toHaveBeenCalled();
		expect(navigateMock).toHaveBeenCalledWith('/login');
	});

	it('blocks a duplicate like while the toggle for this article is in flight', () => {
		likeIsToggling = true;
		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		expect(capturedLikeButtonProps[0].disabled).toBe(true);
	});

	it('lays the page out as the Reading Room: one h1, the reading facts and a named rail', () => {
		const html = renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		expect(html.match(/<h1[ >]/g)).toHaveLength(1);
		expect(html).toContain('aria-label="About this article"');
		expect(html).toMatch(/In this reading<\/h2>/);
		expect(html).toMatch(/<dt[^>]*>Kanji<\/dt><dd[^>]*>21<\/dd>/);
		expect(html).toMatch(/<dt[^>]*>Words<\/dt><dd[^>]*>5<\/dd>/);
		expect(html).toMatch(/<dt[^>]*>Characters<\/dt><dd[^>]*>4<\/dd>/);
		expect(html).toMatch(/Comments<\/h2>/);
		expect(html).not.toContain('unsplash');
	});

	it('counts nothing while processing is still running', () => {
		const article = createArticle();
		article.processing_status = { type: 'article_content_processing', status: 'processing' };

		const html = renderToStaticMarkup(<ArticleContent article={article} />);

		expect(html.match(/Counting…/g)).toHaveLength(2);
		expect(html).not.toContain('role="img"');
	});

	it('gives the owner Edit (still the ?edit=1 modal) and Delete, and the visibility cue', () => {
		const html = renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		expect(articleEditHref('article-uuid')).toBe('/articles/article-uuid?edit=1');
		expect(html).toContain('href="/articles/article-uuid?edit=1"');
		expect(html).toContain('Your article');
		expect(html).toContain('Delete');
		expect(html).toContain('Public');
		expect(html).not.toContain('Moderation');
	});

	it('shows a reader neither owner controls nor the visibility and status cues', () => {
		currentUserMock = { id: 99, isAdmin: false };

		const html = renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		expect(html).not.toContain('Your article');
		expect(html).not.toContain('?edit=1');
		expect(html).not.toContain('Public');
		expect(html).not.toContain('Article status');
	});

	it('keeps Review for admins until moderation moves to the admin panel', () => {
		currentUserMock = { id: 99, isAdmin: true };

		const html = renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		expect(html).toContain('Moderation');
		expect(html).toContain('>Review</button>');
		expect(html).toContain('Article status');
		expect(html).not.toContain('Your article');
	});

	it('labels the save and PDF actions with visible text', () => {
		const html = renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		expect(html).toContain('Save to a catalogue');
		expect(html).toContain('Kanji &amp; words PDF');
		expect(html).toContain('Like · 3');
	});

	it('links a written article to its source by hostname', () => {
		const article = createArticle();
		article.source_link = 'https://www.example.com/news/12345';

		expect(renderToStaticMarkup(<ArticleContent article={article} />)).toMatch(/Source:.*example\.com/);

		article.source_link = 'not a url';
		expect(renderToStaticMarkup(<ArticleContent article={article} />)).not.toContain('Source:');
	});
});
