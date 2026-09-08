import { QueryClient, useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationOptions } from '@tanstack/react-query';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { commentStore } from '@/api/generated/comment/comment';
import { likeLikeInstance } from '@/api/generated/like/like';
import type { CommentListResource } from '@/api/generated/model/commentListResource';
import type { CommentResource } from '@/api/generated/model/commentResource';
import { CommentResourceEntityType } from '@/api/generated/model/commentResourceEntityType';
import { LikeTargetType } from '@/api/generated/model/likeTargetType';
import type { LikeToggleContext } from '@/api/likes/likes';
import axios from '@/services/axios';
import { addComment, fetchComments, getCommentsQueryKey, useLikeCommentMutation } from './comments';

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

vi.mock('@/api/generated/comment/comment', () => ({
	commentStore: vi.fn(),
}));

vi.mock('@/services/axios', () => ({
	default: {
		get: vi.fn(),
	},
}));

describe('comments api', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('posts comments to the generic v1 comment endpoint for articles', async () => {
		const createdComment = {
			id: 1,
			uuid: 'c1d7e9f0-1b2a-4c3d-8e4f-5a6b7c8d9e01',
			entity_uuid: '0fb383ad-e203-43f3-9c15-a34bd1ad1a46',
			entity_type: CommentResourceEntityType.article,
			author_name: 'Alana',
			author_id: 7,
			content: 'hello',
			parent_comment_id: null,
			is_reply: false,
			created_at: '2026-05-04T10:00:00Z',
			updated_at: '2026-05-04T10:00:00Z',
			likes_count: 0,
			is_liked_by_viewer: false,
			replies: [],
		};
		vi.mocked(commentStore).mockResolvedValue(createdComment);

		const result = await addComment(
			'ad69baf6-1a1f-42bd-8176-74ab5fbd69bd',
			101,
			'0fb383ad-e203-43f3-9c15-a34bd1ad1a46',
			{
				content: 'hello',
			},
		);

		expect(result).toBe(createdComment);
		expect(commentStore).toHaveBeenCalledWith({
			entity_type: 'ad69baf6-1a1f-42bd-8176-74ab5fbd69bd',
			entity_id: 101,
			entity_uuid: '0fb383ad-e203-43f3-9c15-a34bd1ad1a46',
			content: 'hello',
		});
	});

	it('posts comments to the generic v1 comment endpoint for catalogues', async () => {
		const createdComment = {
			id: 2,
			uuid: 'c1d7e9f0-1b2a-4c3d-8e4f-5a6b7c8d9e02',
			entity_uuid: '57b661a6-85c5-4369-bb08-3896cc03e853',
			entity_type: CommentResourceEntityType.list,
			author_name: 'Alana',
			author_id: 7,
			content: 'hi',
			parent_comment_id: null,
			is_reply: false,
			created_at: '2026-05-04T10:00:00Z',
			updated_at: '2026-05-04T10:00:00Z',
			likes_count: 0,
			is_liked_by_viewer: false,
			replies: [],
		};
		vi.mocked(commentStore).mockResolvedValue(createdComment);

		const result = await addComment(
			'93edeaab-85d0-44ad-ba2d-4602ab4061ba',
			202,
			'57b661a6-85c5-4369-bb08-3896cc03e853',
			{
				content: 'hi',
			},
		);

		expect(result).toBe(createdComment);
		expect(commentStore).toHaveBeenCalledWith({
			entity_type: '93edeaab-85d0-44ad-ba2d-4602ab4061ba',
			entity_id: 202,
			entity_uuid: '57b661a6-85c5-4369-bb08-3896cc03e853',
			content: 'hi',
		});
	});

	it('fetches direct paginated comment resources without a data wrapper', async () => {
		const commentsResponse = {
			items: [
				{
					id: 3,
					entity_uuid: '57b661a6-85c5-4369-bb08-3896cc03e853',
					entity_type: CommentResourceEntityType.list,
					author_name: 'Alana',
					author_id: 7,
					content: 'direct list comment',
					parent_comment_id: null,
					is_reply: false,
					created_at: '2026-05-04T10:00:00Z',
					updated_at: '2026-05-04T10:00:00Z',
					likes_count: 0,
					is_liked_by_viewer: false,
					replies: [],
				},
			],
			pagination: {
				page: 1,
				per_page: 20,
				total: 1,
				last_page: 1,
				has_more: false,
			},
		};
		vi.mocked(axios.get).mockResolvedValue({ data: commentsResponse });

		await expect(
			fetchComments('catalogue', '57b661a6-85c5-4369-bb08-3896cc03e853', { include_likes: true }),
		).resolves.toBe(commentsResponse);
		expect(axios.get).toHaveBeenCalledWith('v1/catalogues/57b661a6-85c5-4369-bb08-3896cc03e853/comments', {
			params: { include_likes: true },
		});
	});
});

type CommentLikeOptions = UseMutationOptions<
	{ is_liked: boolean; likes_count: number },
	unknown,
	number,
	LikeToggleContext<CommentListResource>
>;

/** The seam always sets these four; narrowing here keeps the assertions free of `!`. */
type LikeCallbacks = Required<Pick<CommentLikeOptions, 'mutationFn' | 'onMutate' | 'onSuccess' | 'onError'>>;

const createComment = (overrides: Partial<CommentResource> = {}): CommentResource => ({
	id: 1,
	uuid: 'c1d7e9f0-1b2a-4c3d-8e4f-5a6b7c8d9e01',
	entity_uuid: '0fb383ad-e203-43f3-9c15-a34bd1ad1a46',
	entity_type: CommentResourceEntityType.article,
	author_name: 'Alana',
	author_id: 7,
	content: 'hello',
	parent_comment_id: null,
	is_reply: false,
	created_at: '2026-05-04T10:00:00Z',
	updated_at: '2026-05-04T10:00:00Z',
	likes_count: 0,
	is_liked_by_viewer: false,
	replies: [],
	...overrides,
});

const THREAD_KEY = getCommentsQueryKey('article', 101);

const createThread = (items: CommentResource[]): CommentListResource => ({
	items,
	pagination: { page: 1, per_page: 10, total: items.length, last_page: 1, has_more: false },
});

/**
 * Drives the real like seam against a real cache: only the React plumbing is mocked, so the
 * assertions below are about what actually lands in the query cache.
 */
const useCommentLikeHarness = (cached?: CommentListResource) => {
	const queryClient = new QueryClient();

	if (cached) {
		queryClient.setQueryData(THREAD_KEY, cached);
	}

	let options!: LikeCallbacks;
	vi.mocked(useQueryClient).mockReturnValue(queryClient);
	vi.mocked(useMutation).mockImplementation((mutationOptions: any) => {
		options = mutationOptions;
		return { mutate: vi.fn(), isPending: false, variables: undefined } as never;
	});

	useLikeCommentMutation(THREAD_KEY);

	return { queryClient, options };
};

const readThread = (queryClient: QueryClient) => queryClient.getQueryData<CommentListResource>(THREAD_KEY);

describe('getCommentsQueryKey', () => {
	it('is stable for the same thread and distinct across parent objects', () => {
		expect(getCommentsQueryKey('article', 101)).toEqual(getCommentsQueryKey('article', 101));
		expect(getCommentsQueryKey('article', 101)).not.toEqual(getCommentsQueryKey('post', 101));
	});
});

describe('useLikeCommentMutation', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('sends the generated like request for the loaded numeric comment id', async () => {
		vi.mocked(likeLikeInstance).mockResolvedValue({ is_liked: true, likes_count: 1 });
		const { options } = useCommentLikeHarness(createThread([createComment()]));

		await options.mutationFn(1, CALLBACK_CONTEXT);

		expect(likeLikeInstance).toHaveBeenCalledWith({
			template_id: LikeTargetType.NUMBER_10,
			real_object_id: 1,
		});
	});

	it('refuses a comment uuid in place of the numeric id', async () => {
		const { options } = useCommentLikeHarness(createThread([createComment()]));

		await expect(
			options.mutationFn('c1d7e9f0-1b2a-4c3d-8e4f-5a6b7c8d9e01' as unknown as number, CALLBACK_CONTEXT),
		).rejects.toThrow();
		expect(likeLikeInstance).not.toHaveBeenCalled();
	});

	it('patches only the liked comment and leaves its neighbours alone', async () => {
		const neighbour = createComment({ id: 2, content: 'second', likes_count: 9, is_liked_by_viewer: true });
		const { queryClient, options } = useCommentLikeHarness(createThread([createComment(), neighbour]));

		await options.onMutate(1, CALLBACK_CONTEXT);

		const items = readThread(queryClient)?.items ?? [];
		expect(items[0]).toMatchObject({ id: 1, is_liked_by_viewer: true, likes_count: 1, content: 'hello' });
		expect(items[1]).toEqual(neighbour);
	});

	it('patches the liked comment when unliking it again', async () => {
		const { queryClient, options } = useCommentLikeHarness(
			createThread([createComment({ likes_count: 4, is_liked_by_viewer: true })]),
		);

		await options.onMutate(1, CALLBACK_CONTEXT);

		expect(readThread(queryClient)?.items[0]).toMatchObject({ is_liked_by_viewer: false, likes_count: 3 });
	});

	it('rolls the whole thread back to its exact pre-click state when the toggle is rejected', async () => {
		const thread = createThread([createComment(), createComment({ id: 2, content: 'second' })]);
		const { queryClient, options } = useCommentLikeHarness(thread);

		const context = await options.onMutate(1, CALLBACK_CONTEXT);
		options.onError({ response: { status: 401 } } as never, 1, context, CALLBACK_CONTEXT);

		expect(readThread(queryClient)).toEqual(thread);
	});

	it('settles the liked comment on the server response rather than the optimistic guess', async () => {
		const { queryClient, options } = useCommentLikeHarness(createThread([createComment()]));

		const context = await options.onMutate(1, CALLBACK_CONTEXT);
		options.onSuccess({ is_liked: true, likes_count: 6 }, 1, context, CALLBACK_CONTEXT);

		expect(readThread(queryClient)?.items[0]).toMatchObject({ is_liked_by_viewer: true, likes_count: 6 });
	});

	it('leaves the thread alone when the liked comment is not in the cached page', async () => {
		const thread = createThread([createComment({ id: 2 })]);
		const { queryClient, options } = useCommentLikeHarness(thread);

		await options.onMutate(1, CALLBACK_CONTEXT);

		expect(readThread(queryClient)).toEqual(thread);
	});
});
