import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { CatalogueForItem } from '@/api/catalogues/cataloguesForItem';
import { articleExportKanjisPdf, articleExportWordsPdf } from '@/api/generated/article/article';
import { catalogueAddItem, catalogueRemoveItem } from '@/api/generated/catalogue/catalogue';
import ArticleContent from './index';

const setQueryDataMock = vi.fn();
const fetchCataloguesForItemMock = vi.fn();
const useQueryMock = vi.fn();
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
		useQuery: (options: any) => useQueryMock(options),
		useQueryClient: () => ({
			setQueryData: setQueryDataMock,
		}),
	};
});

vi.mock('@/api/generated/catalogue/catalogue', () => ({
	catalogueAddItem: vi.fn(),
	catalogueRemoveItem: vi.fn(),
}));

vi.mock('@/api/catalogues/cataloguesForItem', () => ({
	applyCatalogueForItemAction: vi.fn((lists: CatalogueForItem[], catalogueId: number, action: 'add' | 'remove') =>
		lists.map((list) =>
			list.id === catalogueId
				? {
						...list,
						contains_item: action === 'add',
					}
				: list,
		),
	),
	fetchCataloguesForItem: (...args: unknown[]) => fetchCataloguesForItemMock(...args),
}));

vi.mock('@/api/articles/details', () => ({
	useLikeArticleMutation: () => ({ mutate: vi.fn(), isPending: false }),
}));

vi.mock('@/api/articles/hooks/useArticleSubscription', () => ({
	useArticleSubscription: vi.fn(),
}));

vi.mock('@/api/articles/articles', () => ({
	setArticleStatus: vi.fn(),
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
	useModal: (dialogRef: { current: null }, options: { id: string; onClose?: () => void }) => ({
		id: options.id,
		dialogRef,
		isOpen: false,
		isRendered: true,
		open: vi.fn(),
		close: options.onClose ?? vi.fn(),
	}),
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
	ArticleReviewModal: () => <div>Article review modal</div>,
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
		createObjectUrlMock.mockReturnValue('blob:article-kanjis');
		vi.stubGlobal('URL', { createObjectURL: createObjectUrlMock });
		vi.stubGlobal('window', { open: windowOpenMock });
		useQueryMock.mockImplementation(({ queryFn }: { queryFn: () => Promise<CatalogueForItem[]> }) => {
			void queryFn();
			return { data: cataloguesForItemLists };
		});
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

	it('adds article bookmarks through the v1 catalogue item endpoint and reconciles for-item state by list id', async () => {
		renderToStaticMarkup(<ArticleContent article={createArticle()} />);

		await capturedModalProps[0].onListAction(cataloguesForItemLists[0], 'add');

		expect(catalogueAddItem).toHaveBeenCalledWith('d453be67-1519-43e2-94ab-af85b79aeb31', { item_id: 321 });
		expect(setQueryDataMock).toHaveBeenCalledWith(['article-bookmarks', 321], expect.any(Function));

		const updater = setQueryDataMock.mock.calls[0][1] as (lists: CatalogueForItem[]) => CatalogueForItem[];
		expect(updater(cataloguesForItemLists)).toEqual([
			{
				...cataloguesForItemLists[0],
				contains_item: true,
			},
		]);
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
});
