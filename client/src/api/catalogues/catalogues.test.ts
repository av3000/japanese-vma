import { beforeEach, describe, expect, it, vi } from 'vitest';
import { apiCall } from '@/services/api';
import {
	createCatalogue,
	fetchCatalogue,
	fetchCatalogues,
	resolveLegacyCatalogueIdentity,
	stringifyCatalogueTags,
	updateCatalogue,
} from './catalogues';

vi.mock('@/services/api', () => ({
	apiCall: vi.fn(),
}));

const generatedCatalogueApi = vi.hoisted(() => ({
	catalogueIndex: vi.fn(),
	catalogueShow: vi.fn(),
	catalogueStore: vi.fn(),
	catalogueUpdate: vi.fn(),
}));

vi.mock('@/api/generated/catalogue', () => generatedCatalogueApi);

describe('catalogue API adapters', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('serializes form tags into the backend hashtag string format', () => {
		expect(stringifyCatalogueTags([])).toBe('');
		expect(stringifyCatalogueTags(['tokyo', '#study', '  note  '])).toBe('#tokyo #study #note');
	});

	it('calls the v1 catalogue list endpoint with page params merged into the query', async () => {
		generatedCatalogueApi.catalogueIndex.mockResolvedValue({
			items: [],
			pagination: { page: 3, per_page: 12, total: 0, last_page: 1, has_more: false },
		});

		await expect(fetchCatalogues({ search: 'tokyo', include_hashtags: true }, 3)).resolves.toEqual({
			items: [],
			pagination: { page: 3, per_page: 12, total: 0, last_page: 1, has_more: false },
		});

		expect(generatedCatalogueApi.catalogueIndex).toHaveBeenCalledWith({
			search: 'tokyo',
			include_hashtags: true,
			page: 3,
		});
	});

	it('calls the v1 detail, create, and update catalogue endpoints', async () => {
		generatedCatalogueApi.catalogueShow.mockResolvedValue({
			catalogue: { id: 10, uuid: 'uuid-1', title: 'Words', type: 7 },
		});
		generatedCatalogueApi.catalogueStore.mockResolvedValue({ uuid: 'uuid-2' });
		generatedCatalogueApi.catalogueUpdate.mockResolvedValue({
			id: 10,
			uuid: 'uuid-2',
			title: 'Updated',
			type: 7,
			publicity: 1,
		});

		await expect(fetchCatalogue('uuid-1')).resolves.toMatchObject({ uuid: 'uuid-1' });
		await expect(createCatalogue({ title: 'Words', type: 7, tags: '#tokyo', publicity: false })).resolves.toEqual({
			uuid: 'uuid-2',
		});
		await expect(updateCatalogue('uuid-2', { title: 'Updated' })).resolves.toMatchObject({ uuid: 'uuid-2' });

		expect(generatedCatalogueApi.catalogueShow).toHaveBeenCalledWith('uuid-1');
		expect(generatedCatalogueApi.catalogueStore).toHaveBeenCalledWith({
			title: 'Words',
			type: 7,
			tags: '#tokyo',
			publicity: false,
		});
		expect(generatedCatalogueApi.catalogueUpdate).toHaveBeenCalledWith('uuid-2', { title: 'Updated' });
	});

	it('resolves a numeric legacy list id to the canonical catalogue identity', async () => {
		vi.mocked(apiCall).mockResolvedValue({
			list: {
				id: 42,
				uuid: '86f593b4-3f77-44fe-8d42-d8e993f7850b',
				title: 'Legacy list',
			},
		});

		await expect(resolveLegacyCatalogueIdentity('42')).resolves.toEqual({
			id: 42,
			uuid: '86f593b4-3f77-44fe-8d42-d8e993f7850b',
			title: 'Legacy list',
		});

		expect(apiCall).toHaveBeenCalledWith({
			method: 'get',
			path: '/list/42',
		});
	});
});
