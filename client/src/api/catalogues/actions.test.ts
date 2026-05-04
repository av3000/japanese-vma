import { beforeEach, describe, expect, it, vi } from 'vitest';
import { customInstance } from '@/services/orval-mutator';
import { downloadLegacyCataloguePdf } from './legacyCatalogues';
import {
	deleteCatalogue,
	downloadCataloguePdf,
} from './actions';

vi.mock('@/services/orval-mutator', () => ({
	customInstance: vi.fn(),
}));

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

	it('deletes a catalogue through the v1 catalogue endpoint', async () => {
		vi.mocked(customInstance).mockResolvedValue(undefined);

		await deleteCatalogue('d453be67-1519-43e2-94ab-af85b79aeb31');

		expect(customInstance).toHaveBeenCalledWith({
			url: '/catalogues/d453be67-1519-43e2-94ab-af85b79aeb31',
			method: 'DELETE',
		});
	});

	it('keeps PDF export behind the temporary legacy adapter', async () => {
		vi.mocked(downloadLegacyCataloguePdf).mockResolvedValue('blob-data' as never);

		await expect(downloadCataloguePdf(9, 5)).resolves.toBe('blob-data');
		expect(downloadLegacyCataloguePdf).toHaveBeenCalledWith(9, 5);
	});
});
