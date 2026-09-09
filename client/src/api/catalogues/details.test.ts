import { QueryClient, useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationOptions } from '@tanstack/react-query';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { getCatalogueShowQueryKey, useCatalogueShow } from '@/api/generated/catalogue/catalogue';
import { likeLikeInstance } from '@/api/generated/like/like';
import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';
import { LikeTargetType } from '@/api/generated/model/likeTargetType';
import type { LikeToggleContext } from '@/api/likes/likes';
import { mapCatalogueDetail, useCatalogueQuery, useLikeCatalogueMutation } from './details';

/**
 * react-query 5.90 hands every mutation callback a trailing `MutationFunctionContext` that the
 * Like seam does not read; these tests only exercise the arguments it does.
 */
const CALLBACK_CONTEXT = {} as never;

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

vi.mock('@/api/generated/like/like', () => ({
	likeLikeInstance: vi.fn(),
}));

type CatalogueLikeOptions = UseMutationOptions<
	{ is_liked: boolean; likes_count: number },
	unknown,
	number,
	LikeToggleContext<CatalogueDetailResource>
>;

/** The seam always sets these four; narrowing here keeps the assertions free of `!`. */
type LikeCallbacks = Required<Pick<CatalogueLikeOptions, 'mutationFn' | 'onMutate' | 'onSuccess' | 'onError'>>;

/**
 * Drives the real like seam against a real cache: only the React plumbing is mocked, so the
 * assertions below are about what actually lands in the query cache.
 */
const useCatalogueLikeHarness = (cached?: CatalogueDetailResource) => {
	const queryClient = new QueryClient();

	if (cached) {
		queryClient.setQueryData(getCatalogueShowQueryKey(cached.uuid), cached);
	}

	let options!: LikeCallbacks;
	vi.mocked(useQueryClient).mockReturnValue(queryClient);
	vi.mocked(useMutation).mockImplementation((mutationOptions: any) => {
		options = mutationOptions;
		return { mutate: vi.fn(), isPending: false, variables: undefined } as never;
	});

	useLikeCatalogueMutation('catalogue-uuid');

	return { queryClient, options };
};

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

	it('sends the generated like request for the loaded numeric catalogue id', async () => {
		vi.mocked(likeLikeInstance).mockResolvedValue({ is_liked: false, likes_count: 3 });
		const { options } = useCatalogueLikeHarness(createCatalogue());

		await options.mutationFn(55, CALLBACK_CONTEXT);

		expect(likeLikeInstance).toHaveBeenCalledWith({
			template_id: LikeTargetType.NUMBER_8,
			real_object_id: 55,
		});
	});

	it('refuses the catalogue uuid in place of the numeric id', async () => {
		const { options } = useCatalogueLikeHarness(createCatalogue());

		await expect(options.mutationFn('catalogue-uuid' as unknown as number, CALLBACK_CONTEXT)).rejects.toThrow();
		expect(likeLikeInstance).not.toHaveBeenCalled();
	});

	it('patches only the engagement block of the cached catalogue when unliking', async () => {
		const catalogue = createCatalogue();
		const { queryClient, options } = useCatalogueLikeHarness(catalogue);

		await options.onMutate(55, CALLBACK_CONTEXT);

		const patched = queryClient.getQueryData<CatalogueDetailResource>(getCatalogueShowQueryKey('catalogue-uuid'));
		expect(patched?.engagement).toEqual({
			is_liked_by_viewer: false,
			likes_count: 3,
			views_count: 8,
			downloads_count: 2,
			comments_count: 1,
		});
		expect(patched?.title).toBe(catalogue.title);
	});

	it('patches the cached catalogue when liking a previously unliked catalogue', async () => {
		const { queryClient, options } = useCatalogueLikeHarness(
			createCatalogue({
				engagement: {
					is_liked_by_viewer: false,
					likes_count: 4,
					views_count: 8,
					downloads_count: 2,
					comments_count: 1,
				},
			}),
		);

		await options.onMutate(55, CALLBACK_CONTEXT);

		expect(
			queryClient.getQueryData<CatalogueDetailResource>(getCatalogueShowQueryKey('catalogue-uuid'))?.engagement,
		).toMatchObject({ is_liked_by_viewer: true, likes_count: 5 });
	});

	it('rolls the cached catalogue back to its exact pre-click state when the toggle is rejected', async () => {
		const catalogue = createCatalogue();
		const { queryClient, options } = useCatalogueLikeHarness(catalogue);

		const context = await options.onMutate(55, CALLBACK_CONTEXT);
		options.onError({ response: { status: 401 } } as never, 55, context, CALLBACK_CONTEXT);

		expect(queryClient.getQueryData<CatalogueDetailResource>(getCatalogueShowQueryKey('catalogue-uuid'))).toEqual(
			catalogue,
		);
	});

	it('settles the cached catalogue on the server response rather than the optimistic guess', async () => {
		const { queryClient, options } = useCatalogueLikeHarness(createCatalogue());

		const context = await options.onMutate(55, CALLBACK_CONTEXT);
		options.onSuccess({ is_liked: true, likes_count: 11 }, 55, context, CALLBACK_CONTEXT);

		expect(
			queryClient.getQueryData<CatalogueDetailResource>(getCatalogueShowQueryKey('catalogue-uuid'))?.engagement,
		).toMatchObject({ is_liked_by_viewer: true, likes_count: 11 });
	});
});
