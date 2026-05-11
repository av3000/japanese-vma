import { beforeEach, describe, expect, it, vi } from 'vitest';
import { downloadLegacyCataloguePdf } from './legacyCatalogues';
import { downloadCataloguePdf } from './actions';

vi.mock('./legacyCatalogues', async () => {
	const actual = await vi.importActual<typeof import('./legacyCatalogues')>('./legacyCatalogues');
	return {
		...actual,
		downloadLegacyCataloguePdf: vi.fn(),
	};
});

describe('catalogue actions', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('keeps PDF export behind the temporary legacy adapter', async () => {
		vi.mocked(downloadLegacyCataloguePdf).mockResolvedValue('blob-data' as never);

		await expect(downloadCataloguePdf(9, 5)).resolves.toBe('blob-data');
		expect(downloadLegacyCataloguePdf).toHaveBeenCalledWith(9, 5);
	});
});
