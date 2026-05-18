import { useMutation, useQueryClient } from '@tanstack/react-query';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { getCatalogueShowQueryKey, useCatalogueShow } from '@/api/generated/catalogue/catalogue';
import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';
import { toggleInstanceLike } from '@/api/likes/likes';
import { ObjectTemplateType, ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';
import { mapCatalogueDetail, useCatalogueQuery, useLikeCatalogueMutation } from './details';

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
	return {
		...actual,
		useMutation: vi.fn(),
		useQueryClient: vi.fn(),
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

vi.mock('@/api/likes/likes', () => ({
	toggleInstanceLike: vi.fn(),
}));

const createCatalogue = (overrides: Partial<CatalogueDetailResource> = {}): CatalogueDetailResource => ({
	id: 55,
	uuid: 'catalogue-uuid',
	type: 5,
	type_label: 'Articles' as CatalogueDetailResource['type_label'],
	title: 'Useful Articles',
	description: 'Saved for study',
	publicity: 1,
	owner: {
		id: 7,
		uuid: 'owner-uuid',
		name: 'Aki',
	},
	items_count: 3,
	hashtags: [],
	engagement: {
		likes_count: 4,
		views_count: 8,
		downloads_count: 2,
		comments_count: 1,
		is_liked_by_viewer: true,
	},
	items: [],
	created_at: '2026-04-01T12:00:00.000Z',
	updated_at: '2026-04-02T12:00:00.000Z',
	...overrides,
});

describe('catalogue details hooks', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('maps generated detail data to the route-facing catalogue shape', () => {
		const catalogue = mapCatalogueDetail(createCatalogue());

		expect(catalogue.displayName).toBe('Aki');
		expect(catalogue.formattedDate).toBe(new Date('2026-04-01T12:00:00.000Z').toLocaleDateString());
		expect(catalogue.engagement?.is_liked_by_viewer).toBe(true);
	});

	it('delegates catalogue detail loading to the generated show hook', () => {
		vi.mocked(useCatalogueShow).mockReturnValue({ data: undefined, isPending: true, isError: false } as never);

		useCatalogueQuery('catalogue-uuid');

		expect(useCatalogueShow).toHaveBeenCalledWith('catalogue-uuid', {
			query: {
				enabled: true,
				retry: false,
				select: mapCatalogueDetail,
			},
		});
	});

	it('invalidates the catalogue detail query after a successful like toggle', async () => {
		const invalidateQueries = vi.fn();
		let mutationOptions: any;

		vi.mocked(useQueryClient).mockReturnValue({ invalidateQueries } as never);
		vi.mocked(useMutation).mockImplementation((options: any) => {
			mutationOptions = options;
			return { mutate: vi.fn(), isPending: false } as never;
		});
		vi.mocked(toggleInstanceLike).mockResolvedValue({ success: true, like: true });

		useLikeCatalogueMutation('catalogue-uuid');

		await mutationOptions.mutationFn(55);
		expect(toggleInstanceLike).toHaveBeenCalledWith({
			objectType: 'List',
			objectTypeId: ObjectTemplateTypeLegacyId[ObjectTemplateType.LIST],
			instanceId: 55,
		});

		mutationOptions.onSuccess();
		expect(invalidateQueries).toHaveBeenCalledWith({
			queryKey: getCatalogueShowQueryKey('catalogue-uuid'),
		});
	});
});
