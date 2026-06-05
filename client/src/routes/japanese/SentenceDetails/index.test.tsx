import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import SentenceDetails from '.';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { CatalogueForItem } from '@/api/catalogues/cataloguesForItem';

const navigateMock = vi.fn();
const fetchCataloguesForItemMock = vi.fn();
const updateCatalogueForItemMock = vi.fn();
const commentsBlockMock = vi.fn();
const capturedBookmarkModalProps: Array<{
	controller: { id: string; isRendered: boolean };
	lists: CatalogueForItem[];
	loadingListIds: number[];
	onListAction: (list: CatalogueForItem, action: 'add' | 'remove') => Promise<void>;
	title?: string;
	ariaLabel?: string;
}> = [];

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');

	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useNavigate: () => navigateMock,
		useParams: () => ({ sentence_id: '77' }),
	};
});

vi.mock('@/api/sentences/details', () => ({
	useSentenceQuery: vi.fn(() => ({
		data: {
			id: 77,
			uuid: 'sentence-uuid',
			user_id: null,
			tatoeba_entry: '7777',
			content: '火を見ます。',
			kanjis: [
				{
					uuid: 'kanji-uuid',
					character: '火',
					meanings: 'fire',
				},
			],
			words: [],
		},
		isLoading: false,
		error: null,
	})),
}));

vi.mock('@/api/catalogues/cataloguesForItem', () => ({
	optimisticApplyCatalogueForItemAction: vi.fn(
		(lists: CatalogueForItem[], catalogueId: number, action: 'add' | 'remove') =>
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
	addOrRemoveCatalogueForItem: (...args: unknown[]) => updateCatalogueForItemMock(...args),
}));

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({
		user: { id: 7 },
		isAuthenticated: true,
	}),
}));

vi.mock('@/hooks/useModal', () => ({
	useModal: (dialogRef: { current: null }, options: { id: string }) => ({
		id: options.id,
		dialogRef,
		isOpen: false,
		isRendered: true,
		open: vi.fn(),
		close: vi.fn(),
	}),
}));

vi.mock('@/components/features/catalogues/CatalogueBookmarkModal', () => ({
	CatalogueBookmarkModal: (props: {
		controller: { id: string; isRendered: boolean };
		lists: CatalogueForItem[];
		loadingListIds: number[];
		onListAction: (list: CatalogueForItem, action: 'add' | 'remove') => Promise<void>;
		title?: string;
		ariaLabel?: string;
	}) => {
		capturedBookmarkModalProps.push(props);
		return <div>Catalogue bookmark modal</div>;
	},
}));

vi.mock('@/components/features/comment/CommentsBlock', () => ({
	default: (props: Record<string, unknown>) => {
		commentsBlockMock(props);
		return <div>Comments</div>;
	},
}));

describe('SentenceDetails', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		capturedBookmarkModalProps.length = 0;
		fetchCataloguesForItemMock.mockResolvedValue([
			{
				id: 8,
				uuid: 'catalogue-uuid',
				title: 'Known sentences',
				type: 8,
				type_label: 'Known Sentences',
				publicity: 0,
				contains_item: true,
			},
		]);
	});

	it('renders the v1 sentence detail response', () => {
		const html = renderToStaticMarkup(<SentenceDetails />);

		expect(html).toContain('火を見ます。');
		expect(html).toContain('7777');
		expect(html).toContain('火');
		expect(html).toContain('fire');
	});

	it('does not expand sentence comments before F9 adds the v1 sentence comment contract', () => {
		renderToStaticMarkup(<SentenceDetails />);

		expect(commentsBlockMock).not.toHaveBeenCalled();
	});

	it('uses the shared catalogue bookmark modal for sentence catalogue actions', () => {
		renderToStaticMarkup(<SentenceDetails />);

		expect(capturedBookmarkModalProps[0]).toMatchObject({
			controller: {
				id: 'sentence-bookmark-modal',
				isRendered: true,
			},
			loadingListIds: [],
			title: 'Choose Sentence List to add',
			ariaLabel: 'Choose Sentence List to add',
		});
		expect(capturedBookmarkModalProps[0].onListAction).toBeTypeOf('function');
	});
});
