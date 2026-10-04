import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { catalogueResolveLegacyId, useCatalogueShow } from '@/api/generated/catalogue/catalogue';
import CatalogueEditPage from './index';

const useParamsMock = vi.fn();
const useNavigateMock = vi.fn();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		useParams: () => useParamsMock(),
		useNavigate: () => useNavigateMock,
	};
});

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
	return {
		...actual,
		useMutation: vi.fn(() => ({ isPending: false, mutate: vi.fn() })),
		useQueryClient: vi.fn(() => ({ invalidateQueries: vi.fn() })),
	};
});

vi.mock('@/api/generated/catalogue/catalogue', async () => {
	const actual = await vi.importActual<typeof import('@/api/generated/catalogue/catalogue')>(
		'@/api/generated/catalogue/catalogue',
	);
	return {
		...actual,
		useCatalogueShow: vi.fn(),
		catalogueResolveLegacyId: vi.fn(),
	};
});

const formProps: Array<Record<string, unknown>> = [];

vi.mock('@/components/features/catalogues/CatalogueForm', () => ({
	CatalogueForm: (props: Record<string, unknown>) => {
		formProps.push(props);
		return <div>Catalogue form</div>;
	},
}));

const UUID = 'd453be67-1519-43e2-94ab-af85b79aeb31';

const render = () =>
	renderToStaticMarkup(
		<MemoryRouter>
			<CatalogueEditPage />
		</MemoryRouter>,
	);

const showCatalogue = (overrides: Record<string, unknown> = {}) =>
	vi.mocked(useCatalogueShow).mockReturnValue({
		data: { uuid: UUID, title: 'My catalogue', type: 5, publicity: 1, hashtags: [], items_count: 0, ...overrides },
		isPending: false,
		isError: false,
	} as never);

describe('CatalogueEditPage', () => {
	beforeEach(() => {
		formProps.length = 0;
		useParamsMock.mockReturnValue({ catalogueId: UUID });
	});

	it('loads canonical UUID edit routes directly without legacy identity resolution', () => {
		showCatalogue();

		const html = render();

		expect(html).toContain('Catalogue form');
		expect(useCatalogueShow).toHaveBeenCalledWith(UUID, {
			query: { enabled: true },
		});
		expect(catalogueResolveLegacyId).not.toHaveBeenCalled();
	});

	it('renders one h1 with links back to the catalogue and leaves the type open while it is empty', () => {
		showCatalogue();

		const html = render();

		expect(html.match(/<h1[ >]/g)).toHaveLength(1);
		expect(html).toMatch(/<h1[^>]*>Edit catalogue<\/h1>/);
		expect(html).toContain(`href="/catalogues/${UUID}"`);
		expect(formProps.at(-1)).toMatchObject({ isTypeLocked: false, requireChanges: true });
	});

	it('locks the type once the catalogue has items', () => {
		showCatalogue({ items_count: 3 });

		render();

		expect(formProps.at(-1)).toMatchObject({ isTypeLocked: true });
	});

	it('shows a plain message inside the page when the catalogue cannot be loaded', () => {
		vi.mocked(useCatalogueShow).mockReturnValue({ data: undefined, isPending: false, isError: true } as never);

		const html = render();

		expect(html).toContain('role="alert"');
		expect(html).toContain('This catalogue could not be loaded.');
		expect(html).toContain('href="/catalogues"');
		expect(formProps).toHaveLength(0);
	});
});
