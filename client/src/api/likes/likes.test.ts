import { QueryClient } from '@tanstack/react-query';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { likeLikeInstance } from '@/api/generated/like/like';
import { LikeTargetType } from '@/api/generated/model/likeTargetType';
import { ObjectTemplateType } from '@/shared/constants/enums';
import { ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';
import {
	buildLikeToggleMutationOptions,
	getLikeInstanceMutationKey,
	LIKE_TARGET_TYPES,
	type LikeCacheBinding,
} from './likes';

/**
 * react-query 5.90 hands every mutation callback a trailing `MutationFunctionContext` that the
 * Like seam does not read; these tests only exercise the arguments it does.
 */
const CALLBACK_CONTEXT = {} as never;

vi.mock('@/api/generated/like/like', () => ({
	likeLikeInstance: vi.fn(),
}));

interface CachedArticle {
	id: number;
	title: string;
	engagement: { is_liked_by_viewer: boolean; likes_count: number };
}

const QUERY_KEY = ['article', 'article-uuid'];

const binding: LikeCacheBinding<CachedArticle> = {
	queryKey: QUERY_KEY,
	read: (article) => ({
		is_liked: article.engagement.is_liked_by_viewer,
		likes_count: article.engagement.likes_count,
	}),
	write: (article, _instanceId, next) => ({
		...article,
		engagement: { is_liked_by_viewer: next.is_liked, likes_count: next.likes_count },
	}),
};

const createCached = (overrides: Partial<CachedArticle['engagement']> = {}): CachedArticle => ({
	id: 7,
	title: 'A study article',
	engagement: { is_liked_by_viewer: false, likes_count: 3, ...overrides },
});

/** The seam always sets these four; narrowing here keeps the assertions free of `!`. */
type LikeCallbacks = Required<
	Pick<
		ReturnType<typeof buildLikeToggleMutationOptions<CachedArticle>>,
		'mutationKey' | 'mutationFn' | 'onMutate' | 'onSuccess' | 'onError'
	>
>;

const setup = (cached?: CachedArticle) => {
	const queryClient = new QueryClient();

	if (cached) {
		queryClient.setQueryData(QUERY_KEY, cached);
	}

	const options = buildLikeToggleMutationOptions<CachedArticle>({
		queryClient,
		template: ObjectTemplateType.ARTICLE,
		binding,
	}) as LikeCallbacks;

	return { queryClient, options };
};

describe('like toggle transport', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('sends the generated request model with the mapped template id', async () => {
		vi.mocked(likeLikeInstance).mockResolvedValue({ is_liked: true, likes_count: 4 });

		const { options } = setup(createCached());

		await expect(options.mutationFn(7, CALLBACK_CONTEXT)).resolves.toEqual({ is_liked: true, likes_count: 4 });
		expect(likeLikeInstance).toHaveBeenCalledWith({
			template_id: LikeTargetType.NUMBER_1,
			real_object_id: 7,
		});
	});

	it('keys every toggle of one target kind under one addressable mutation key', () => {
		const { options } = setup();

		expect(options.mutationKey).toEqual(getLikeInstanceMutationKey(ObjectTemplateType.ARTICLE));
		expect(getLikeInstanceMutationKey(ObjectTemplateType.COMMENT)).not.toEqual(options.mutationKey);
	});
});

describe('like toggle cache behaviour', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('optimistically likes an unliked target', async () => {
		const { queryClient, options } = setup(createCached({ is_liked_by_viewer: false, likes_count: 3 }));

		await options.onMutate(7, CALLBACK_CONTEXT);

		expect(queryClient.getQueryData<CachedArticle>(QUERY_KEY)?.engagement).toEqual({
			is_liked_by_viewer: true,
			likes_count: 4,
		});
	});

	it('optimistically unlikes an already liked target', async () => {
		const { queryClient, options } = setup(createCached({ is_liked_by_viewer: true, likes_count: 3 }));

		await options.onMutate(7, CALLBACK_CONTEXT);

		expect(queryClient.getQueryData<CachedArticle>(QUERY_KEY)?.engagement).toEqual({
			is_liked_by_viewer: false,
			likes_count: 2,
		});
	});

	it('never renders a negative count when the cached total is already zero', async () => {
		const { queryClient, options } = setup(createCached({ is_liked_by_viewer: true, likes_count: 0 }));

		await options.onMutate(7, CALLBACK_CONTEXT);

		expect(queryClient.getQueryData<CachedArticle>(QUERY_KEY)?.engagement.likes_count).toBe(0);
	});

	it('leaves unrelated fields of the cached record untouched', async () => {
		const { queryClient, options } = setup(createCached());

		await options.onMutate(7, CALLBACK_CONTEXT);

		expect(queryClient.getQueryData<CachedArticle>(QUERY_KEY)).toMatchObject({ id: 7, title: 'A study article' });
	});

	it('writes the authoritative server state on success', async () => {
		const { queryClient, options } = setup(createCached());

		const context = await options.onMutate(7, CALLBACK_CONTEXT);
		// The server may disagree with the optimistic guess, e.g. another device liked in between.
		options.onSuccess({ is_liked: true, likes_count: 9 }, 7, context, CALLBACK_CONTEXT);

		expect(queryClient.getQueryData<CachedArticle>(QUERY_KEY)?.engagement).toEqual({
			is_liked_by_viewer: true,
			likes_count: 9,
		});
	});

	it('restores the exact pre-click snapshot when the toggle fails', async () => {
		const cached = createCached({ is_liked_by_viewer: true, likes_count: 12 });
		const { queryClient, options } = setup(cached);

		const context = await options.onMutate(7, CALLBACK_CONTEXT);
		expect(queryClient.getQueryData<CachedArticle>(QUERY_KEY)).not.toEqual(cached);

		options.onError({ response: { status: 401 } } as never, 7, context, CALLBACK_CONTEXT);

		expect(queryClient.getQueryData<CachedArticle>(QUERY_KEY)).toEqual(cached);
	});

	it('leaves the cache alone when an unauthenticated toggle fails before anything was cached', async () => {
		const { queryClient, options } = setup();

		const context = await options.onMutate(7, CALLBACK_CONTEXT);
		options.onError({ response: { status: 401 } } as never, 7, context, CALLBACK_CONTEXT);

		expect(queryClient.getQueryData(QUERY_KEY)).toBeUndefined();
	});

	it('skips the optimistic write when the domain has no cached like state to flip', async () => {
		const queryClient = new QueryClient();
		const cached = { id: 7, title: 'Countless', engagement: { is_liked_by_viewer: false, likes_count: 3 } };
		queryClient.setQueryData(QUERY_KEY, cached);

		const options = buildLikeToggleMutationOptions<CachedArticle>({
			queryClient,
			template: ObjectTemplateType.POST,
			binding: { ...binding, read: () => undefined },
		}) as LikeCallbacks;

		const context = await options.onMutate(7, CALLBACK_CONTEXT);
		expect(queryClient.getQueryData(QUERY_KEY)).toEqual(cached);

		options.onSuccess({ is_liked: true, likes_count: 4 }, 7, context, CALLBACK_CONTEXT);
		expect(queryClient.getQueryData<CachedArticle>(QUERY_KEY)?.engagement.likes_count).toBe(4);
	});

	it('cancels in-flight reads so a late refetch cannot undo the optimistic write', async () => {
		const { queryClient, options } = setup(createCached());
		const cancelQueries = vi.spyOn(queryClient, 'cancelQueries');

		await options.onMutate(7, CALLBACK_CONTEXT);

		expect(cancelQueries).toHaveBeenCalledWith({ queryKey: QUERY_KEY });
	});
});

describe('like target mapping', () => {
	it('covers exactly the likeable subset the contract declares', () => {
		// A backend change to the likeable set is the one drift no type can catch: adding a fifth
		// template widens the generated enum without touching this table.
		expect(Object.values(LIKE_TARGET_TYPES).sort((a, b) => a - b)).toEqual(
			Object.values(LikeTargetType).sort((a, b) => a - b),
		);
	});

	it.each([
		ObjectTemplateType.ARTICLE,
		ObjectTemplateType.LIST,
		ObjectTemplateType.POST,
		ObjectTemplateType.COMMENT,
	] as const)('maps %s to the same number the rest of the app knows it by', (template) => {
		expect(LIKE_TARGET_TYPES[template]).toBe(ObjectTemplateTypeLegacyId[template]);
	});
});

describe('like instance id guard', () => {
	// The guard is not exported: it only exists inside the seam, so it is exercised through it.
	const rejects = async (instanceId: unknown) => {
		const { options } = setup(createCached());

		await expect(options.mutationFn(instanceId as number, CALLBACK_CONTEXT)).rejects.toThrow(
			/loaded positive integer/,
		);
		expect(likeLikeInstance).not.toHaveBeenCalled();
	};

	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('refuses a uuid route parameter instead of coercing it to a numeric id', async () => {
		await rejects(ObjectTemplateType.ARTICLE);
	});

	it.each([[Number.NaN], [0], [-1], [1.5], [undefined], [null]])('refuses %s', async (value) => {
		await rejects(value);
	});
});
