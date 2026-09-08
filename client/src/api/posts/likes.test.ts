import { QueryClient, useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationOptions } from '@tanstack/react-query';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { likeLikeInstance } from '@/api/generated/like/like';
import { LikeTargetType } from '@/api/generated/model/likeTargetType';
import type { PostDetailResource } from '@/api/generated/model/postDetailResource';
import type { LikeToggleContext } from '@/api/likes/likes';
import { useLikePostMutation } from './likes';
import { getPostDetailQueryKey } from './reads';

/**
 * react-query 5.90 hands every mutation callback a trailing `MutationFunctionContext` that the
 * Like seam does not read; these tests only exercise the arguments it does.
 */
const CALLBACK_CONTEXT = {} as never;

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
	return { ...actual, useMutation: vi.fn(), useQueryClient: vi.fn() };
});

vi.mock('@/api/generated/like/like', () => ({
	likeLikeInstance: vi.fn(),
}));

type PostLikeOptions = UseMutationOptions<
	{ is_liked: boolean; likes_count: number },
	unknown,
	number,
	LikeToggleContext<PostDetailResource>
>;

/** The seam always sets these four; narrowing here keeps the assertions free of `!`. */
type LikeCallbacks = Required<Pick<PostLikeOptions, 'mutationFn' | 'onMutate' | 'onSuccess' | 'onError'>>;

const createPost = (overrides: Partial<PostDetailResource> = {}): PostDetailResource => ({
	id: 31,
	uuid: 'post-uuid',
	entity_type_uuid: 'a4b78a83-f180-49b5-9f8a-39500cd8fabf',
	title: 'How do I read this kanji?',
	topic: 1,
	topic_label: 'Content-related',
	locked: false,
	content: 'Stuck on the second character.',
	author: { id: 7, uuid: 'author-uuid', name: 'Aki' },
	hashtags: [],
	engagement: {
		stats: { likes_count: '4', views_count: '12', downloads_count: '0', comments_count: '2' },
	},
	created_at: '2026-04-01T12:00:00.000Z',
	updated_at: '2026-04-02T12:00:00.000Z',
	...overrides,
});

/**
 * Drives the real like seam against a real cache: only the React plumbing is mocked, so the
 * assertions below are about what actually lands in the query cache.
 */
const usePostLikeHarness = (cached?: PostDetailResource) => {
	const queryClient = new QueryClient();

	if (cached) {
		queryClient.setQueryData(getPostDetailQueryKey(cached.uuid), cached);
	}

	let options!: LikeCallbacks;
	vi.mocked(useQueryClient).mockReturnValue(queryClient);
	vi.mocked(useMutation).mockImplementation((mutationOptions: any) => {
		options = mutationOptions;
		return { mutate: vi.fn(), isPending: false, variables: undefined } as never;
	});

	useLikePostMutation('post-uuid');

	return { queryClient, options };
};

const readCachedPost = (queryClient: QueryClient) =>
	queryClient.getQueryData<PostDetailResource>(getPostDetailQueryKey('post-uuid'));

describe('useLikePostMutation', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('sends the generated like request for the loaded numeric post id', async () => {
		vi.mocked(likeLikeInstance).mockResolvedValue({ is_liked: true, likes_count: 5 });
		const { options } = usePostLikeHarness(createPost());

		await options.mutationFn(31, CALLBACK_CONTEXT);

		expect(likeLikeInstance).toHaveBeenCalledWith({
			template_id: LikeTargetType.NUMBER_9,
			real_object_id: 31,
		});
	});

	it('refuses the post uuid in place of the numeric id', async () => {
		const { options } = usePostLikeHarness(createPost());

		await expect(options.mutationFn('post-uuid' as unknown as number, CALLBACK_CONTEXT)).rejects.toThrow();
		expect(likeLikeInstance).not.toHaveBeenCalled();
	});

	it('makes no optimistic guess, because the Post read contract carries no viewer like state', async () => {
		const post = createPost();
		const { queryClient, options } = usePostLikeHarness(post);

		await options.onMutate(31, CALLBACK_CONTEXT);

		expect(readCachedPost(queryClient)).toEqual(post);
	});

	it('writes the served count back onto the cached post when a like succeeds', async () => {
		const { queryClient, options } = usePostLikeHarness(createPost());

		const context = await options.onMutate(31, CALLBACK_CONTEXT);
		options.onSuccess({ is_liked: true, likes_count: 5 }, 31, context, CALLBACK_CONTEXT);

		expect(readCachedPost(queryClient)?.engagement.stats).toEqual({
			// The wire types every engagement count as a string, so the patch has to as well.
			likes_count: '5',
			views_count: '12',
			downloads_count: '0',
			comments_count: '2',
		});
	});

	it('writes the served count back when an unlike succeeds', async () => {
		const { queryClient, options } = usePostLikeHarness(createPost());

		const context = await options.onMutate(31, CALLBACK_CONTEXT);
		options.onSuccess({ is_liked: false, likes_count: 3 }, 31, context, CALLBACK_CONTEXT);

		expect(readCachedPost(queryClient)?.engagement.stats?.likes_count).toBe('3');
	});

	it('leaves the rest of the cached post untouched', async () => {
		const post = createPost();
		const { queryClient, options } = usePostLikeHarness(post);

		const context = await options.onMutate(31, CALLBACK_CONTEXT);
		options.onSuccess({ is_liked: true, likes_count: 5 }, 31, context, CALLBACK_CONTEXT);

		expect(readCachedPost(queryClient)).toMatchObject({ id: 31, title: post.title, locked: false });
	});

	it('tolerates a post nobody has engaged with yet, where stats are null', async () => {
		const { queryClient, options } = usePostLikeHarness(createPost({ engagement: { stats: null } }));

		const context = await options.onMutate(31, CALLBACK_CONTEXT);
		options.onSuccess({ is_liked: true, likes_count: 1 }, 31, context, CALLBACK_CONTEXT);

		expect(readCachedPost(queryClient)?.engagement.stats).toBeNull();
	});

	it('leaves the cached post exactly as it was when the toggle is rejected', async () => {
		const post = createPost();
		const { queryClient, options } = usePostLikeHarness(post);

		const context = await options.onMutate(31, CALLBACK_CONTEXT);
		options.onError({ response: { status: 401 } } as never, 31, context, CALLBACK_CONTEXT);

		expect(readCachedPost(queryClient)).toEqual(post);
	});
});
