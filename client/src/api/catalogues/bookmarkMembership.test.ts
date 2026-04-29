import { beforeEach, describe, expect, it, vi } from 'vitest';
import axios from '@/services/axios';
import {
	applyCatalogueMembershipAction,
	fetchElementCatalogueMembership,
	filterCatalogueMembershipByType,
} from './bookmarkMembership';
import { resolveLegacyCatalogueIdentity } from './legacyCatalogues';

vi.mock('@/services/axios', () => ({
	default: {
		post: vi.fn(),
	},
}));

vi.mock('./legacyCatalogues', async () => {
	const actual = await vi.importActual<typeof import('./legacyCatalogues')>('./legacyCatalogues');
	return {
		...actual,
		resolveLegacyCatalogueIdentity: vi.fn(),
	};
});

describe('bookmarkMembership', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('returns canonical UUID-bearing catalogue membership items', async () => {
		vi.mocked(axios.post).mockResolvedValue({
			data: {
				lists: [
					{
						id: 5,
						uuid: '1f466d4c-0d41-4c17-80fd-458d8325958a',
						title: 'Known radicals',
						type: 1,
						elementBelongsToList: true,
					},
					{
						id: 6,
						title: 'Missing uuid',
						type: 5,
						elementBelongsToList: false,
					},
				],
			},
		});
		vi.mocked(resolveLegacyCatalogueIdentity).mockResolvedValue({
			id: 6,
			uuid: '6685fa88-b53e-41a1-98dc-3535988f7c4a',
			title: 'Resolved title',
		});

		await expect(fetchElementCatalogueMembership('42')).resolves.toEqual([
			{
				id: 5,
				uuid: '1f466d4c-0d41-4c17-80fd-458d8325958a',
				title: 'Known radicals',
				type: 1,
				elementBelongsToList: true,
			},
			{
				id: 6,
				uuid: '6685fa88-b53e-41a1-98dc-3535988f7c4a',
				title: 'Resolved title',
				type: 5,
				elementBelongsToList: false,
			},
		]);

		expect(axios.post).toHaveBeenCalledWith('user/lists/contain', { elementId: '42' });
		expect(resolveLegacyCatalogueIdentity).toHaveBeenCalledWith(6);
	});

	it('filters the normalized membership list by catalogue type', () => {
		expect(
			filterCatalogueMembershipByType(
				[
					{ id: 1, uuid: 'a', title: 'A', type: 1, elementBelongsToList: false },
					{ id: 2, uuid: 'b', title: 'B', type: 5, elementBelongsToList: true },
					{ id: 3, uuid: 'c', title: 'C', type: 6, elementBelongsToList: false },
				],
				[1, 6],
			),
		).toEqual([
			{ id: 1, uuid: 'a', title: 'A', type: 1, elementBelongsToList: false },
			{ id: 3, uuid: 'c', title: 'C', type: 6, elementBelongsToList: false },
		]);
	});

	it('updates bookmark membership locally without exposing numeric route fallbacks', () => {
		expect(
			applyCatalogueMembershipAction(
				[
					{ id: 1, uuid: 'a', title: 'A', type: 1, elementBelongsToList: false },
					{ id: 2, uuid: 'b', title: 'B', type: 5, elementBelongsToList: true },
				],
				1,
				'add',
			),
		).toEqual([
			{ id: 1, uuid: 'a', title: 'A', type: 1, elementBelongsToList: true },
			{ id: 2, uuid: 'b', title: 'B', type: 5, elementBelongsToList: true },
		]);
	});
});
