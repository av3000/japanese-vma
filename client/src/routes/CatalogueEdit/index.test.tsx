import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import CatalogueEditPage from './index';
import { useCatalogueShow } from '@/api/generated/catalogue/catalogue';
import { resolveLegacyCatalogueIdentity } from '@/api/catalogues/legacyCatalogues';

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
	};
});

vi.mock('@/api/catalogues/legacyCatalogues', async () => {
	const actual = await vi.importActual<typeof import('@/api/catalogues/legacyCatalogues')>(
		'@/api/catalogues/legacyCatalogues',
	);
	return {
		...actual,
		resolveLegacyCatalogueIdentity: vi.fn(),
	};
});

vi.mock('@/components/features/catalogues/CatalogueForm', () => ({
	CatalogueForm: () => <div>Catalogue form</div>,
}));

describe('CatalogueEditPage', () => {
	it('loads canonical UUID edit routes directly without legacy identity resolution', () => {
		useParamsMock.mockReturnValue({ catalogueId: 'd453be67-1519-43e2-94ab-af85b79aeb31' });
		vi.mocked(useCatalogueShow).mockReturnValue({
			data: {
				catalogue: {
					title: 'My catalogue',
					type: 5,
					publicity: 1,
					hashtags: [],
				},
			},
			isPending: false,
			isError: false,
		} as never);

		const html = renderToStaticMarkup(<CatalogueEditPage />);

		expect(html).toContain('Catalogue form');
		expect(useCatalogueShow).toHaveBeenCalledWith('d453be67-1519-43e2-94ab-af85b79aeb31', {
			query: { enabled: true },
		});
		expect(resolveLegacyCatalogueIdentity).not.toHaveBeenCalled();
	});
});
