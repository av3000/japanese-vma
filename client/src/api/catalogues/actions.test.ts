import { beforeEach, describe, expect, it, vi } from 'vitest';
import axios from '@/services/axios';
import { customInstance } from '@/services/orval-mutator';
import { downloadLegacyCataloguePdf } from './legacyCatalogues';
import {
	addItemToCatalogue,
	deleteCatalogue,
	downloadCataloguePdf,
	removeItemFromCatalogue,
	updateCatalogueMembership,
} from './actions';

vi.mock('@/services/orval-mutator', () => ({
	customInstance: vi.fn(),
}));

vi.mock('@/services/axios', () => ({
	default: {
		post: vi.fn(),
	},
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

	it('adds and removes catalogue items through the v1 catalogue item endpoints', async () => {
		vi.mocked(customInstance).mockResolvedValue(undefined);

		await addItemToCatalogue('d453be67-1519-43e2-94ab-af85b79aeb31', 321);
		await removeItemFromCatalogue('d453be67-1519-43e2-94ab-af85b79aeb31', 321);

		expect(customInstance).toHaveBeenNthCalledWith(1, {
			url: '/catalogues/d453be67-1519-43e2-94ab-af85b79aeb31/items',
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			data: { item_id: 321 },
		});
		expect(customInstance).toHaveBeenNthCalledWith(2, {
			url: '/catalogues/d453be67-1519-43e2-94ab-af85b79aeb31/items/321',
			method: 'DELETE',
		});
	});

	it('updates bookmark membership through the legacy membership contract adapter', async () => {
		vi.mocked(axios.post).mockResolvedValue({ data: {} });

		await updateCatalogueMembership({ catalogueId: 7, elementId: 42, action: 'remove' });

		expect(axios.post).toHaveBeenCalledWith('user/list/removeitemwhileaway', { listId: 7, elementId: 42 });
	});

	it('keeps PDF export behind the temporary legacy adapter', async () => {
		vi.mocked(downloadLegacyCataloguePdf).mockResolvedValue('blob-data' as never);

		await expect(downloadCataloguePdf(9, 5)).resolves.toBe('blob-data');
		expect(downloadLegacyCataloguePdf).toHaveBeenCalledWith(9, 5);
	});
});
