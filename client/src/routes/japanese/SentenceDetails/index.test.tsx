import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { CatalogueForItem } from '@/api/catalogues/cataloguesForItem';
import SentenceDetails from './index';

const navigateMock = vi.fn();
const fetchCataloguesForItemMock = vi.fn();
const updateCatalogueForItemMock = vi.fn();
const commentsBlockMock = vi.fn();

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
	updateCatalogueForItem: (...args: unknown[]) => updateCatalogueForItemMock(...args),
}));

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({
		user: { id: 7 },
		isAuthenticated: true,
	}),
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
});
