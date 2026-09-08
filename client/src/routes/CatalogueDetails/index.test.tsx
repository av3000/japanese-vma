import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import { useCatalogueQuery } from '@/api/catalogues/details';
import { catalogueResolveLegacyId } from '@/api/generated/catalogue/catalogue';
import CatalogueDetailsPage from './index';

const useParamsMock = vi.fn();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		useParams: () => useParamsMock(),
	};
});

vi.mock('@/api/catalogues/details', () => ({
	useCatalogueQuery: vi.fn(),
}));

vi.mock('@/api/generated/catalogue/catalogue', async () => {
	const actual = await vi.importActual<typeof import('@/api/generated/catalogue/catalogue')>(
		'@/api/generated/catalogue/catalogue',
	);
	return {
		...actual,
		catalogueResolveLegacyId: vi.fn(),
	};
});

vi.mock('./CatalogueContent', () => ({
	default: ({ catalogue }: { catalogue: { title: string } }) => <div>{catalogue.title}</div>,
}));

describe('CatalogueDetailsPage', () => {
	it('renders the detail loading family while the first query is pending', () => {
		useParamsMock.mockReturnValue({ catalogueId: 'd453be67-1519-43e2-94ab-af85b79aeb31' });
		vi.mocked(useCatalogueQuery).mockReturnValue({
			data: undefined,
			isPending: true,
			isError: false,
		} as never);

		const html = renderToStaticMarkup(<CatalogueDetailsPage />);

		expect(html).toContain('aria-busy="true"');
		expect(html).toContain('data-loading-family="detail"');
		expect(html).toContain('Loading page.');
		expect(html).not.toContain('alt="Loading..."');
	});

	it('loads canonical UUID routes directly without legacy identity resolution', () => {
		useParamsMock.mockReturnValue({ catalogueId: 'd453be67-1519-43e2-94ab-af85b79aeb31' });
		vi.mocked(useCatalogueQuery).mockReturnValue({
			data: { title: 'My catalogue' },
			isPending: false,
			isError: false,
		} as never);

		const html = renderToStaticMarkup(<CatalogueDetailsPage />);

		expect(html).toContain('My catalogue');
		expect(useCatalogueQuery).toHaveBeenCalledWith('d453be67-1519-43e2-94ab-af85b79aeb31');
		expect(catalogueResolveLegacyId).not.toHaveBeenCalled();
	});
});
