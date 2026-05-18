import { beforeEach, describe, expect, it, vi } from 'vitest';
import { apiCall } from '@/services/api';
import { resolveLegacyCatalogueIdentity, stringifyCatalogueTags } from './legacyCatalogues';

vi.mock('@/services/api', () => ({
	apiCall: vi.fn(),
}));

describe('legacy catalogue adapters', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('serializes form tags into the backend hashtag string format', () => {
		expect(stringifyCatalogueTags([])).toBe('');
		expect(stringifyCatalogueTags(['tokyo', '#study', '  note  '])).toBe('#tokyo #study #note');
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
