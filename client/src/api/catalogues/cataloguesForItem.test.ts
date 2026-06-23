import { beforeEach, describe, expect, it, vi } from 'vitest';
import { catalogueAddItem, catalogueForItem, catalogueRemoveItem } from '@/api/generated/catalogue/catalogue';
import * as cataloguesForItemModule from './cataloguesForItem';

const {
	addOrRemoveCatalogueForItem,
	deriveCatalogueWidgetState,
	optimisticApplyCatalogueForItemAction,
	fetchCataloguesForItem,
} = cataloguesForItemModule;

vi.mock('@/api/generated/catalogue/catalogue', () => ({
	catalogueAddItem: vi.fn(),
	catalogueForItem: vi.fn(),
	catalogueRemoveItem: vi.fn(),
}));

describe('cataloguesForItem', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('reads catalogues for an item through the generated v1 endpoint', async () => {
		vi.mocked(catalogueForItem).mockResolvedValue({
			items: [
				{
					id: 5,
					uuid: '1f466d4c-0d41-4c17-80fd-458d8325958a',
					title: 'Known radicals',
					type: 1,
					type_label: 'Known Radicals',
					publicity: 0,
					contains_item: true,
				},
				{
					id: 6,
					uuid: '6685fa88-b53e-41a1-98dc-3535988f7c4a',
					title: 'Resolved title',
					type: 5,
					type_label: 'Radicals',
					publicity: 1,
					contains_item: false,
				},
			],
		});

		await expect(fetchCataloguesForItem('42')).resolves.toEqual([
			{
				id: 5,
				uuid: '1f466d4c-0d41-4c17-80fd-458d8325958a',
				title: 'Known radicals',
				type: 1,
				type_label: 'Known Radicals',
				publicity: 0,
				contains_item: true,
			},
			{
				id: 6,
				uuid: '6685fa88-b53e-41a1-98dc-3535988f7c4a',
				title: 'Resolved title',
				type: 5,
				type_label: 'Radicals',
				publicity: 1,
				contains_item: false,
			},
		]);

		expect(catalogueForItem).toHaveBeenCalledWith({ item_id: 42 });
	});

	it('passes optional server-side filters through the generated endpoint params', async () => {
		vi.mocked(catalogueForItem).mockResolvedValue({ items: [] });

		await fetchCataloguesForItem(42, {
			types: [1, 6],
			search: 'Tokyo',
		});

		expect(catalogueForItem).toHaveBeenCalledWith({
			item_id: 42,
			'types[]': [1, 6],
			search: 'Tokyo',
		});
	});

	it('updates for-item state locally without exposing numeric route fallbacks', () => {
		expect(
			optimisticApplyCatalogueForItemAction(
				[
					{
						id: 1,
						uuid: 'a',
						title: 'A',
						type: 1,
						type_label: 'Known Radicals',
						publicity: 0,
						contains_item: false,
					},
					{
						id: 2,
						uuid: 'b',
						title: 'B',
						type: 5,
						type_label: 'Radicals',
						publicity: 1,
						contains_item: true,
					},
				],
				1,
				'add',
			),
		).toEqual([
			{
				id: 1,
				uuid: 'a',
				title: 'A',
				type: 1,
				type_label: 'Known Radicals',
				publicity: 0,
				contains_item: true,
			},
			{
				id: 2,
				uuid: 'b',
				title: 'B',
				type: 5,
				type_label: 'Radicals',
				publicity: 1,
				contains_item: true,
			},
		]);
	});

	it('derives bookmark and known state from picker membership', () => {
		expect(
			deriveCatalogueWidgetState(
				[
					{
						id: 1,
						uuid: 'saved-list',
						title: 'Saved Words',
						type: 7,
						type_label: 'Words',
						publicity: 0,
						contains_item: true,
					},
					{
						id: 2,
						uuid: 'known-list',
						title: 'Known Words',
						type: 3,
						type_label: 'Known Words',
						publicity: 0,
						contains_item: false,
					},
				],
				3,
			),
		).toEqual({
			isBookmarked: true,
			isKnown: false,
		});
	});

	it('writes catalogue for-item additions through the v1 catalogue item endpoint', async () => {
		vi.mocked(catalogueAddItem).mockResolvedValue([] as never);

		await addOrRemoveCatalogueForItem({
			list: {
				id: 7,
				uuid: 'd453be67-1519-43e2-94ab-af85b79aeb31',
				title: 'Known words',
				type: 3,
				type_label: 'Known Words',
				publicity: 0,
				contains_item: false,
			},
			elementId: 42,
			action: 'add',
		});

		expect(catalogueAddItem).toHaveBeenCalledWith('d453be67-1519-43e2-94ab-af85b79aeb31', { item_id: 42 });
	});

	it('writes catalogue for-item removals through the v1 catalogue item endpoint', async () => {
		vi.mocked(catalogueRemoveItem).mockResolvedValue(204 as never);

		await addOrRemoveCatalogueForItem({
			list: {
				id: 7,
				uuid: 'd453be67-1519-43e2-94ab-af85b79aeb31',
				title: 'Known words',
				type: 3,
				type_label: 'Known Words',
				publicity: 0,
				contains_item: true,
			},
			elementId: 42,
			action: 'remove',
		});

		expect(catalogueRemoveItem).toHaveBeenCalledWith('d453be67-1519-43e2-94ab-af85b79aeb31', 42);
	});
});
