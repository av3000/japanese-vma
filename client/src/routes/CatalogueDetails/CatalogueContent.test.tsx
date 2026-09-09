import { isValidElement, type ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
	catalogueExportKanjisPdf,
	catalogueExportWordsPdf,
	catalogueRemoveItem,
	getCatalogueIndexQueryKey,
	getCatalogueShowQueryKey,
	useCatalogueDestroy,
} from '@/api/generated/catalogue/catalogue';
import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';
import CatalogueContent from './CatalogueContent';

const useNavigateMock = vi.fn();
const likeMutateMock = vi.fn();
let likeIsToggling = false;
let isAuthenticatedMock = true;
const capturedLikeButtonProps: Array<{ onClick?: () => void; disabled?: boolean; 'aria-pressed'?: boolean }> = [];
const setQueryDataMock = vi.fn();
const invalidateQueriesMock = vi.fn();
const catalogueDestroyMutateMock = vi.fn();
const capturedCatalogueItemsProps: Array<{
	onRemoveItem: (id: number) => void;
}> = [];
const capturedDeleteModalProps: Array<{
	onDelete: () => void;
}> = [];
const capturedPdfButtonProps: Array<{
	onClick?: () => void;
}> = [];
const createObjectUrlMock = vi.fn();
const windowOpenMock = vi.fn();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		useNavigate: () => useNavigateMock,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
	};
});

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
	return {
		...actual,
		useMutation: vi.fn((options: any) => ({
			mutate: vi.fn(async (variables: unknown) => {
				const result = await options.mutationFn(variables);
				await options.onSuccess?.(result, variables, undefined);
				return result;
			}),
			isPending: false,
		})),
		useQueryClient: vi.fn(() => ({ setQueryData: setQueryDataMock, invalidateQueries: invalidateQueriesMock })),
	};
});

vi.mock('@/api/generated/catalogue/catalogue', async () => {
	const actual = await vi.importActual<typeof import('@/api/generated/catalogue/catalogue')>(
		'@/api/generated/catalogue/catalogue',
	);
	return {
		...actual,
		catalogueExportKanjisPdf: vi.fn(),
		catalogueExportWordsPdf: vi.fn(),
		catalogueRemoveItem: vi.fn(),
		useCatalogueDestroy: vi.fn(() => ({
			mutate: catalogueDestroyMutateMock,
			isPending: false,
		})),
	};
});

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({
		user: { id: 7, isAdmin: false },
		isAuthenticated: isAuthenticatedMock,
	}),
}));

vi.mock('@/api/catalogues/details', () => ({
	useLikeCatalogueMutation: vi.fn(() => ({
		mutate: likeMutateMock,
		isPending: false,
		isTogglingInstance: () => likeIsToggling,
	})),
}));

vi.mock('@/components/shared/Icon', () => ({
	Icon: ({ name }: { name: string }) => <span>{name}</span>,
}));

vi.mock('@/components/shared/Button', () => ({
	Button: ({
		children,
		onClick,
		disabled,
		...rest
	}: {
		children: ReactNode;
		onClick?: () => void;
		disabled?: boolean;
		'aria-label'?: string;
		'aria-pressed'?: boolean;
	}) => {
		if (isValidElement<{ name?: string }>(children) && children.props.name === 'filePdfSolid') {
			capturedPdfButtonProps.push({ onClick });
		}

		if (rest['aria-label']?.endsWith('this catalogue')) {
			capturedLikeButtonProps.push({ onClick, disabled, 'aria-pressed': rest['aria-pressed'] });
		}

		return (
			<button type="button" onClick={onClick} disabled={disabled}>
				{children}
			</button>
		);
	},
}));

vi.mock('@/components/features/catalogues/CatalogueItems', () => ({
	CatalogueItems: (props: { onRemoveItem: (id: number) => void }) => {
		capturedCatalogueItemsProps.push(props);
		return <div>Catalogue items</div>;
	},
}));

vi.mock('@/components/features/comment/CommentsBlock', () => ({
	default: () => <div>Comments</div>,
}));

vi.mock('@/components/features/DeleteInstanceModal', () => ({
	DeleteInstanceModal: (props: { onDelete: () => void }) => {
		capturedDeleteModalProps.push(props);
		return <div>Delete modal</div>;
	},
}));

const createCatalogue = (overrides: Partial<CatalogueDetailResource> = {}): CatalogueDetailResource => ({
	id: 55,
	uuid: 'catalogue-uuid',
	type: 5,
	type_label: 'Articles' as CatalogueDetailResource['type_label'],
	title: 'Useful Articles',
	description: 'Saved for study',
	publicity: 1,
	owner: {
		id: 7,
		uuid: 'owner-uuid',
		name: 'Aki',
	},
	items_count: 0,
	hashtags: [],
	engagement: {
		likes_count: 4,
		views_count: 8,
		downloads_count: 2,
		comments_count: 1,
		is_liked_by_viewer: true,
	},
	items: [],
	created_at: '2026-04-01T12:00:00.000Z',
	updated_at: '2026-04-02T12:00:00.000Z',
	...overrides,
});

describe('CatalogueContent', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		capturedCatalogueItemsProps.length = 0;
		capturedDeleteModalProps.length = 0;
		capturedPdfButtonProps.length = 0;
		capturedLikeButtonProps.length = 0;
		likeIsToggling = false;
		isAuthenticatedMock = true;
		createObjectUrlMock.mockReturnValue('blob:catalogue-pdf');
		vi.stubGlobal('URL', { createObjectURL: createObjectUrlMock });
		vi.stubGlobal('window', { open: windowOpenMock });
		vi.mocked(catalogueExportKanjisPdf).mockResolvedValue('%PDF-kanji' as never);
		vi.mocked(catalogueExportWordsPdf).mockResolvedValue('%PDF-words' as never);
		vi.mocked(catalogueRemoveItem).mockResolvedValue(204 as never);
	});

	it('renders the liked icon from the catalogue detail engagement payload', () => {
		const html = renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue() as any} />);

		expect(html).toContain('thumbsUpSolid');
		expect(html).not.toContain('thumbsUpRegular');
		expect(capturedLikeButtonProps[0]['aria-pressed']).toBe(true);
	});

	it('renders the unfilled like icon for a catalogue the viewer has not liked', () => {
		const html = renderToStaticMarkup(
			<CatalogueContent
				catalogue={
					createCatalogue({
						engagement: {
							likes_count: 4,
							views_count: 8,
							downloads_count: 2,
							comments_count: 1,
							is_liked_by_viewer: false,
						},
					}) as any
				}
			/>,
		);

		expect(html).toContain('thumbsUpRegular');
		expect(capturedLikeButtonProps[0]['aria-pressed']).toBe(false);
	});

	it('likes through the loaded numeric catalogue id rather than the uuid route parameter', () => {
		renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue() as any} />);

		capturedLikeButtonProps[0].onClick?.();

		expect(likeMutateMock).toHaveBeenCalledWith(55);
	});

	it('sends an anonymous reader to login instead of a like the endpoint would reject', () => {
		isAuthenticatedMock = false;
		renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue() as any} />);

		capturedLikeButtonProps[0].onClick?.();

		expect(likeMutateMock).not.toHaveBeenCalled();
		expect(useNavigateMock).toHaveBeenCalledWith('/login');
	});

	it('blocks a duplicate like while the toggle for this catalogue is in flight', () => {
		likeIsToggling = true;
		renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue() as any} />);

		expect(capturedLikeButtonProps[0].disabled).toBe(true);
	});

	it('removes catalogue items through the direct v1 catalogue item endpoint and updates the detail cache', async () => {
		renderToStaticMarkup(
			<CatalogueContent
				catalogue={
					createCatalogue({
						items_count: 2,
						items: [
							{ id: 11, title: 'First item' },
							{ id: 12, title: 'Second item' },
						] as never,
					}) as any
				}
			/>,
		);

		await capturedCatalogueItemsProps[0].onRemoveItem(12);

		expect(catalogueRemoveItem).toHaveBeenCalledWith('catalogue-uuid', 12);
		expect(setQueryDataMock).toHaveBeenCalledWith(['/catalogues/catalogue-uuid'], expect.any(Function));

		const updater = setQueryDataMock.mock.calls[0][1] as (
			old: CatalogueDetailResource | undefined,
		) => CatalogueDetailResource | undefined;
		const updated = updater(
			createCatalogue({
				items_count: 2,
				items: [
					{ id: 11, title: 'First item' },
					{ id: 12, title: 'Second item' },
				] as never,
			}),
		);

		expect(updated?.items).toEqual([{ id: 11, title: 'First item' }]);
		expect(updated?.items_count).toBe(1);
	});

	it('deletes catalogues through the generated v1 destroy mutation and clears related cache keys', async () => {
		renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue() as any} />);

		capturedDeleteModalProps[0].onDelete();

		expect(catalogueDestroyMutateMock).toHaveBeenCalledWith({ uuid: 'catalogue-uuid' });

		const options = vi.mocked(useCatalogueDestroy).mock.calls[0]?.[0];
		expect(options).toBeDefined();

		await options?.mutation?.onSuccess?.('', { uuid: 'catalogue-uuid' }, undefined, {} as never);

		expect(invalidateQueriesMock).toHaveBeenNthCalledWith(1, {
			queryKey: getCatalogueIndexQueryKey(),
		});
		expect(invalidateQueriesMock).toHaveBeenNthCalledWith(2, {
			queryKey: getCatalogueShowQueryKey('catalogue-uuid'),
		});
		expect(useNavigateMock).toHaveBeenCalledWith('/catalogues');
	});

	it('downloads kanji catalogue pdf through the generated v1 endpoint', async () => {
		renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue({ type: 6 }) as any} />);

		await capturedPdfButtonProps[0].onClick?.();

		expect(catalogueExportKanjisPdf).toHaveBeenCalledWith('catalogue-uuid', { responseType: 'blob' });
		expect(catalogueExportWordsPdf).not.toHaveBeenCalled();
		expect(createObjectUrlMock).toHaveBeenCalledWith(expect.any(Blob));
		expect(windowOpenMock).toHaveBeenCalledWith('blob:catalogue-pdf');
	});

	it('downloads word catalogue pdf through the generated v1 endpoint', async () => {
		renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue({ type: 7 }) as any} />);

		await capturedPdfButtonProps[0].onClick?.();

		expect(catalogueExportWordsPdf).toHaveBeenCalledWith('catalogue-uuid', { responseType: 'blob' });
		expect(catalogueExportKanjisPdf).not.toHaveBeenCalled();
		expect(createObjectUrlMock).toHaveBeenCalledWith(expect.any(Blob));
		expect(windowOpenMock).toHaveBeenCalledWith('blob:catalogue-pdf');
	});

	it('does not render a pdf download button for unsupported catalogue types', () => {
		const html = renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue({ type: 5 }) as any} />);

		expect(html).not.toContain('filePdfSolid');
		expect(capturedPdfButtonProps).toHaveLength(0);
	});
});
