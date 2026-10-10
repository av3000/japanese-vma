import { Children, isValidElement, type ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
	catalogueExportKanjisPdf,
	catalogueExportRadicalsPdf,
	catalogueExportSentencesPdf,
	catalogueExportWordsPdf,
} from '@/api/generated/catalogue/catalogue';
import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';
import { downloadFile } from '@/helpers/downloadFile';
import CatalogueContent from './CatalogueContent';

const useNavigateMock = vi.fn();
const likeMutateMock = vi.fn();
let likeIsToggling = false;
let isAuthenticatedMock = true;
const capturedLikeButtonProps: Array<{ onClick?: () => void; disabled?: boolean; 'aria-pressed'?: boolean }> = [];
const setQueryDataMock = vi.fn();
const invalidateQueriesMock = vi.fn();
const deleteMutateMock = vi.fn();
let currentUserIdMock = 7;
const capturedCatalogueItemsProps: Array<{
	catalogueUuid: string;
	isOwner: boolean;
}> = [];
const capturedDeleteModalProps: Array<{
	onDelete: () => void;
}> = [];
const capturedPdfButtonProps: Array<{
	onClick?: () => void;
}> = [];

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
		catalogueExportRadicalsPdf: vi.fn(),
		catalogueExportSentencesPdf: vi.fn(),
	};
});

vi.mock('@/api/catalogues/hooks/useDeleteCatalogueMutation', () => ({
	useDeleteCatalogueMutation: () => ({ mutate: deleteMutateMock, isPending: false }),
}));

vi.mock('@/helpers/downloadFile', async () => {
	const actual = await vi.importActual<typeof import('@/helpers/downloadFile')>('@/helpers/downloadFile');
	return {
		...actual,
		downloadFile: vi.fn(),
	};
});

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({
		user: { id: currentUserIdMock, isAdmin: false },
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
		const hasIcon = (name: string) =>
			Children.toArray(children).some(
				(child) => isValidElement<{ name?: string }>(child) && child.props.name === name,
			);

		if (hasIcon('filePdfSolid')) {
			capturedPdfButtonProps.push({ onClick });
		}

		if (rest['aria-pressed'] !== undefined) {
			capturedLikeButtonProps.push({ onClick, disabled, 'aria-pressed': rest['aria-pressed'] });
		}

		if (to) return <a href={to}>{children}</a>;

		return (
			<button type="button" onClick={onClick} disabled={disabled}>
				{children}
			</button>
		);
	},
}));

vi.mock('@/components/features/catalogues/CatalogueItems', () => ({
	CatalogueItems: (props: { catalogueUuid: string; isOwner: boolean }) => {
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
	// Article catalogues have no PDF export, so the default fixture renders no download button.
	type: 9,
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
	jlpt_levels: null,
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
		currentUserIdMock = 7;
		vi.mocked(catalogueExportKanjisPdf).mockResolvedValue('%PDF-kanji' as never);
		vi.mocked(catalogueExportWordsPdf).mockResolvedValue('%PDF-words' as never);
		vi.mocked(catalogueExportRadicalsPdf).mockResolvedValue('%PDF-radicals' as never);
		vi.mocked(catalogueExportSentencesPdf).mockResolvedValue('%PDF-sentences' as never);
	});

	it('renders the liked icon from the catalogue detail engagement payload', () => {
		const html = renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue() as any} />);

		expect(html).toContain('thumbsUpSolid');
		expect(html).not.toContain('thumbsUpRegular');
		expect(capturedLikeButtonProps[0]['aria-pressed']).toBe(true);
	});

	it('offers Study for kanji, words and radicals catalogues with items, to visitors too', () => {
		isAuthenticatedMock = false;
		currentUserIdMock = 99;
		const study = (type: number, itemsCount = 3) =>
			renderToStaticMarkup(
				<CatalogueContent catalogue={createCatalogue({ type: type as any, items_count: itemsCount }) as any} />,
			);

		expect(study(6)).toContain('href="/catalogues/catalogue-uuid/study"');
		expect(study(3)).toContain('>Study</a>');
		// The default fixture is an Articles catalogue; sentences have no cards either.
		expect(study(9)).not.toContain('Study');
		expect(study(8)).not.toContain('Study');
	});

	it('hides Study for an empty catalogue and tells its owner how to fill it', () => {
		const owner = renderToStaticMarkup(
			<CatalogueContent catalogue={createCatalogue({ type: 6, items_count: 0 }) as any} />,
		);
		expect(owner).not.toContain('>Study</a>');
		expect(owner).toContain('Add kanji, words or radicals to study this catalogue.');

		currentUserIdMock = 99;
		const visitor = renderToStaticMarkup(
			<CatalogueContent catalogue={createCatalogue({ type: 6, items_count: 0 }) as any} />,
		);
		expect(visitor).not.toContain('Study');
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

	it('deletes through the shared catalogue delete mutation, then returns to the list', () => {
		renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue() as any} />);

		capturedDeleteModalProps[0].onDelete();

		expect(deleteMutateMock).toHaveBeenCalledWith('catalogue-uuid', expect.any(Object));
		deleteMutateMock.mock.calls[0][1].onSuccess();
		expect(useNavigateMock).toHaveBeenCalledWith('/catalogues');
	});

	it('lays the page out with one h1, a named rail, the items and comments', () => {
		const html = renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue({ items_count: 12 }) as any} />);

		expect(html.match(/<h1[ >]/g)).toHaveLength(1);
		expect(html).toContain('aria-label="About this catalogue"');
		expect(html).toMatch(/In this catalogue<\/h2>/);
		expect(html).toMatch(/<dt[^>]*>Items<\/dt><dd[^>]*>12<\/dd>/);
		expect(html).toMatch(/Comments<\/h2>/);
		expect(html).toContain('Saved for study');
		expect(html).not.toContain('smartphone-screen');
		expect(html).not.toContain('No description yet.');
		expect(capturedCatalogueItemsProps[0]).toMatchObject({ catalogueUuid: 'catalogue-uuid', isOwner: true });
	});

	it('gives the owner named Edit and Delete actions and the visibility cue', () => {
		const html = renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue() as any} />);

		expect(html).toContain('href="/catalogues/catalogue-uuid/edit"');
		expect(html).toContain('Edit catalogue');
		expect(html).toContain('Delete catalogue');
		expect(html).toContain('Public');
	});

	it('shows a visitor neither owner actions nor the visibility cue', () => {
		currentUserIdMock = 99;

		const html = renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue() as any} />);

		expect(html).not.toContain('Edit catalogue');
		expect(html).not.toContain('Delete catalogue');
		expect(html).not.toContain('Public');
	});

	it.each([
		['kanji', 6, catalogueExportKanjisPdf],
		['kanji', 2, catalogueExportKanjisPdf],
		['words', 7, catalogueExportWordsPdf],
		['words', 3, catalogueExportWordsPdf],
		['radicals', 5, catalogueExportRadicalsPdf],
		['radicals', 1, catalogueExportRadicalsPdf],
		['sentences', 8, catalogueExportSentencesPdf],
		['sentences', 4, catalogueExportSentencesPdf],
	])('downloads a %s catalogue (type %i) through its own generated v1 endpoint', async (_kind, type, client) => {
		renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue({ type: type as any }) as any} />);

		await capturedPdfButtonProps[0].onClick?.();

		expect(client).toHaveBeenCalledWith('catalogue-uuid', { responseType: 'blob' });

		const otherClients = [
			catalogueExportKanjisPdf,
			catalogueExportWordsPdf,
			catalogueExportRadicalsPdf,
			catalogueExportSentencesPdf,
		].filter((candidate) => candidate !== client);

		otherClients.forEach((candidate) => expect(candidate).not.toHaveBeenCalled());
		expect(downloadFile).toHaveBeenCalledWith('Useful Articles.pdf', expect.any(Blob));
	});

	it('surfaces a failed export instead of only logging it', async () => {
		const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
		vi.mocked(catalogueExportSentencesPdf).mockRejectedValue(new Error('boom') as never);

		renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue({ type: 8 }) as any} />);

		await capturedPdfButtonProps[0].onClick?.();

		expect(downloadFile).not.toHaveBeenCalled();
		expect(consoleErrorSpy).toHaveBeenCalled();

		consoleErrorSpy.mockRestore();
	});

	it('does not render a pdf download button for unsupported catalogue types', () => {
		const html = renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue({ type: 9 }) as any} />);

		expect(html).not.toContain('filePdfSolid');
		expect(capturedPdfButtonProps).toHaveLength(0);
	});
});
