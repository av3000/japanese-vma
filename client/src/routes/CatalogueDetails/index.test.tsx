import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import CatalogueDetailsPage from './index';
import { useCatalogueQuery } from '@/api/catalogues/details';
import { resolveLegacyCatalogueIdentity } from '@/api/catalogues/legacyCatalogues';

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

vi.mock('@/api/catalogues/legacyCatalogues', async () => {
	const actual = await vi.importActual<typeof import('@/api/catalogues/legacyCatalogues')>(
		'@/api/catalogues/legacyCatalogues',
	);
	return {
		...actual,
		resolveLegacyCatalogueIdentity: vi.fn(),
	};
});

vi.mock('./CatalogueContent', () => ({
	default: ({ catalogue }: { catalogue: { title: string } }) => <div>{catalogue.title}</div>,
}));

describe('CatalogueDetailsPage', () => {
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
		expect(resolveLegacyCatalogueIdentity).not.toHaveBeenCalled();
	});
});
