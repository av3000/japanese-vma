import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';
import CatalogueContent from './CatalogueContent';

const useNavigateMock = vi.fn();

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
		useMutation: vi.fn(() => ({ mutate: vi.fn(), isPending: false })),
		useQueryClient: vi.fn(() => ({ setQueryData: vi.fn(), invalidateQueries: vi.fn() })),
	};
});

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({
		user: { id: 7, isAdmin: false },
		isAuthenticated: true,
	}),
}));

vi.mock('@/api/catalogues/details', () => ({
	useLikeCatalogueMutation: vi.fn(() => ({ mutate: vi.fn(), isPending: false })),
}));

vi.mock('@/components/shared/Icon', () => ({
	Icon: ({ name }: { name: string }) => <span>{name}</span>,
}));

vi.mock('@/components/shared/Button', () => ({
	Button: ({ children }: { children: ReactNode }) => <button type="button">{children}</button>,
}));

vi.mock('@/components/features/catalogues/CatalogueItems', () => ({
	CatalogueItems: () => <div>Catalogue items</div>,
}));

vi.mock('@/components/features/comment/CommentsBlock', () => ({
	default: () => <div>Comments</div>,
}));

vi.mock('@/components/features/DeleteInstanceModal', () => ({
	DeleteInstanceModal: () => <div>Delete modal</div>,
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
	items: '',
	created_at: '2026-04-01T12:00:00.000Z',
	updated_at: '2026-04-02T12:00:00.000Z',
	...overrides,
});

describe('CatalogueContent', () => {
	it('renders the liked icon from the catalogue detail engagement payload', () => {
		const html = renderToStaticMarkup(<CatalogueContent catalogue={createCatalogue() as any} />);

		expect(html).toContain('thumbsUpSolid');
		expect(html).not.toContain('thumbsUpRegular');
	});
});
