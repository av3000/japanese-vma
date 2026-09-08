import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { CatalogueForItem } from '@/api/catalogues/cataloguesForItem';
import { articleExportKanjisPdf, articleExportWordsPdf } from '@/api/generated/article/article';
import { catalogueAddItem, catalogueRemoveItem } from '@/api/generated/catalogue/catalogue';
import ArticleContent from './index';

const fetchCataloguesForItemMock = vi.fn();
const windowOpenMock = vi.fn();
const createObjectUrlMock = vi.fn();
const capturedModalProps: Array<{
	lists: CatalogueForItem[];
	loadingListIds: number[];
	onListAction: (list: CatalogueForItem, action: 'add' | 'remove') => Promise<void>;
}> = [];
const capturedPdfModalProps: Array<{
	onDownload: (type: 'kanji' | 'words') => Promise<void>;
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

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useNavigate: () => vi.fn(),
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
	useLikeArticleMutation: () => ({ mutate: vi.fn(), isPending: false }),
}));

vi.mock('@/api/articles/hooks/useArticleSubscription', () => ({
	useArticleSubscription: vi.fn(),
}));

vi.mock('@/api/articles/moderation', () => ({
	useArticleStatusMutation: (
		articleUuid: string,
		callbacks: { onSuccess?: () => void; onError?: () => void } = {},
	) => {
		capturedStatusMutationArgs.push([articleUuid, callbacks]);
		return { mutate: statusMutateMock, isPending: statusMutationIsPending };
	},
}));

vi.mock('@/api/generated/article/article', () => ({
	articleDestroy: vi.fn(),
	articleExportKanjisPdf: vi.fn(),
	articleExportWordsPdf: vi.fn(),
}));

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({
		user: { id: 7, isAdmin: false },
		isAuthenticated: true,
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

vi.mock('@/components/features/comment/CommentsBlock', () => ({
	default: () => <div>Comments</div>,
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

vi.mock('@/components/ui/article-status', () => ({
	default: () => <div>Article status</div>,
}));

vi.mock('@/components/ui/badge', () => ({
	Badge: ({ children }: { children: ReactNode }) => <span>{children}</span>,
}));

vi.mock('../ArticleEditModal', () => ({
	default: () => <div>Article edit modal</div>,
}));

const createArticle = () =>
	({
		id: 321,
		uuid: 'article-uuid',
		title_jp: 'Study Article',
		content_jp: 'Body',
		status: 1,
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
		},
		hashtags: [],
		processing_status: {
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
		statusMutationIsPending = false;
		createObjectUrlMock.mockReturnValue('blob:article-kanjis');
		vi.stubGlobal('URL', { createObjectURL: createObjectUrlMock });
		vi.stubGlobal('window', { open: windowOpenMock });
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

	it('downloads article kanji pdf through the generated v1 article endpoint', async () => {
		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		await capturedPdfModalProps[0].onDownload('kanji');

		expect(articleExportKanjisPdf).toHaveBeenCalledWith('article-uuid', { responseType: 'blob' });
		expect(articleExportWordsPdf).not.toHaveBeenCalled();
		expect(createObjectUrlMock).toHaveBeenCalledWith(expect.any(Blob));
		expect(windowOpenMock).toHaveBeenCalledWith('blob:article-kanjis');
	});

	it('downloads article words pdf through the generated v1 article endpoint', async () => {
		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		await capturedPdfModalProps[0].onDownload('words');

		expect(articleExportWordsPdf).toHaveBeenCalledWith('article-uuid', { responseType: 'blob' });
		expect(articleExportKanjisPdf).not.toHaveBeenCalled();
		expect(createObjectUrlMock).toHaveBeenCalledWith(expect.any(Blob));
		expect(windowOpenMock).toHaveBeenCalledWith('blob:article-kanjis');
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
});
