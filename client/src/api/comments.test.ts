import { QueryClient, useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationOptions } from '@tanstack/react-query';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
	commentGetArticleComments,
	commentGetCatalogueComments,
	commentGetPostComments,
	commentGetSentenceComments,
	getCommentGetArticleCommentsQueryKey,
} from '@/api/generated/comment/comment';
import { likeLikeInstance } from '@/api/generated/like/like';
import type { CommentListResource } from '@/api/generated/model/commentListResource';
import type { CommentReplyResource } from '@/api/generated/model/commentReplyResource';
import type { CommentResource } from '@/api/generated/model/commentResource';
import { LikeTargetType } from '@/api/generated/model/likeTargetType';
import type { LikeToggleContext } from '@/api/likes/likes';
import { ObjectTemplateType } from '@/shared/constants/enums';
import {
	COMMENT_LIST_PARAMS,
	COMMENT_PARENTS,
	appendReply,
	getCommentsQueryKey,
	patchCommentContent,
	prependComment,
	readCommentWriteError,
	removeComment,
	replaceComment,
	useLikeCommentMutation,
} from './comments';

/**
 * react-query 5.90 hands every mutation callback a trailing `MutationFunctionContext`
 * that the Like seam does not read; these tests only exercise the arguments it does.
 */
const CALLBACK_CONTEXT = {} as never;

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
	return { ...actual, useMutation: vi.fn(), useQueryClient: vi.fn() };
});

vi.mock('@/api/generated/like/like', () => ({
	likeLikeInstance: vi.fn(),
}));

vi.mock('@/api/generated/comment/comment', async () => {
	const actual = await vi.importActual<typeof import('@/api/generated/comment/comment')>(
		'@/api/generated/comment/comment',
	);
	return {
		...actual,
		commentGetArticleComments: vi.fn(),
		commentGetCatalogueComments: vi.fn(),
		commentGetPostComments: vi.fn(),
		commentGetSentenceComments: vi.fn(),
		commentStore: vi.fn(),
		commentUpdate: vi.fn(),
		commentDestroy: vi.fn(),
		commentReplies: vi.fn(),
	};
});

const ARTICLE_UUID = '0fb383ad-e203-43f3-9c15-a34bd1ad1a46';

const createComment = (overrides: Partial<CommentResource> = {}): CommentResource => ({
	id: 1,
	uuid: 'c1d7e9f0-1b2a-4c3d-8e4f-5a6b7c8d9e01',
	entity_uuid: ARTICLE_UUID,
	entity_type_uuid: ObjectTemplateType.ARTICLE,
	entity_type_label: 'Article',
	author: { id: 7, name: 'Alana', uuid: '9c2f6e1a-0b3c-4d5e-8f60-112233445566' },
	content: 'hello',
	parent_comment_id: null,
	is_reply: false,
	likes_count: 0,
	viewer: { is_liked: false, can_edit: false, can_delete: false },
	replies_count: 0,
	replies: [],
	created_at: '2026-05-04T10:00:00Z',
	updated_at: '2026-05-04T10:00:00Z',
	...overrides,
});

const createReply = (overrides: Partial<CommentReplyResource> = {}): CommentReplyResource => {
	const { replies: _replies, replies_count: _repliesCount, ...base } = createComment();

	return {
		...base,
		id: 50,
		uuid: 'r1d7e9f0-1b2a-4c3d-8e4f-5a6b7c8d9e50',
		content: 'a reply',
		parent_comment_id: 1,
		is_reply: true,
		...overrides,
	};
};

const createThread = (items: CommentResource[]): CommentListResource => ({
	items,
	pagination: { page: 1, per_page: 20, total: items.length, last_page: 1, has_more: false },
});

describe('comment parent map', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	/**
	 * One case per parent. The previous seam built `v1/${objectType}s/...` by hand,
	 * so a parent whose route did not follow the pluralization rule would have
	 * silently 404'd; this is the regression net against that returning.
	 */
	it.each([
		['article', commentGetArticleComments, ObjectTemplateType.ARTICLE],
		['catalogue', commentGetCatalogueComments, ObjectTemplateType.LIST],
		['post', commentGetPostComments, ObjectTemplateType.POST],
		['sentence', commentGetSentenceComments, ObjectTemplateType.SENTENCE],
	] as const)('reads %s threads through its own generated client', (parent, client, template) => {
		COMMENT_PARENTS[parent].fetchThread(ARTICLE_UUID, COMMENT_LIST_PARAMS);

		expect(client).toHaveBeenCalledWith(ARTICLE_UUID, COMMENT_LIST_PARAMS);
		expect(COMMENT_PARENTS[parent].template).toBe(template);
	});

	it('asks for nested replies rather than a flat page', () => {
		expect(COMMENT_LIST_PARAMS.include_replies).toBe(true);
		expect(COMMENT_LIST_PARAMS.replies_limit).toBeGreaterThan(0);
	});
});

describe('getCommentsQueryKey', () => {
	it('delegates to the generated key helper', () => {
		expect(getCommentsQueryKey('article', ARTICLE_UUID)).toEqual(
			getCommentGetArticleCommentsQueryKey(ARTICLE_UUID, COMMENT_LIST_PARAMS),
		);
	});

	it('is stable for one thread and distinct across parents', () => {
		expect(getCommentsQueryKey('article', ARTICLE_UUID)).toEqual(getCommentsQueryKey('article', ARTICLE_UUID));
		expect(getCommentsQueryKey('article', ARTICLE_UUID)).not.toEqual(getCommentsQueryKey('post', ARTICLE_UUID));
	});
});

describe('cache mutators', () => {
	it('prepends a new comment and counts it as a conversation', () => {
		const thread = createThread([createComment()]);

		const next = prependComment(thread, createComment({ id: 2, uuid: 'new-uuid', content: 'newest' }));

		expect(next.items.map((item) => item.content)).toEqual(['newest', 'hello']);
		expect(next.pagination.total).toBe(2);
	});

	it('appends a reply to its root without touching the page total', () => {
		const thread = createThread([createComment({ replies_count: 1, replies: [createReply()] })]);

		const next = appendReply(thread, 1, createReply({ id: 51, uuid: 'reply-2', content: 'second reply' }));

		expect(next.items[0].replies.map((reply) => reply.content)).toEqual(['a reply', 'second reply']);
		expect(next.items[0].replies_count).toBe(2);
		expect(next.pagination.total).toBe(1);
	});

	it('replaces an edited root while keeping the replies it already loaded', () => {
		const reply = createReply();
		const thread = createThread([createComment({ replies_count: 4, replies: [reply] })]);

		// The edit response does not re-read the subtree, so it carries no replies.
		const next = replaceComment(thread, createComment({ content: 'edited', replies: [], replies_count: 0 }));

		expect(next.items[0].content).toBe('edited');
		expect(next.items[0].replies).toEqual([reply]);
		expect(next.items[0].replies_count).toBe(4);
	});

	it('replaces an edited reply in place', () => {
		const thread = createThread([createComment({ replies_count: 1, replies: [createReply()] })]);

		const next = replaceComment(thread, createReply({ content: 'edited reply' }));

		expect(next.items[0].replies[0].content).toBe('edited reply');
		expect(next.items[0].content).toBe('hello');
	});

	it('removes a root with its loaded replies and decrements the page total', () => {
		const neighbour = createComment({ id: 2, uuid: 'second' });
		const thread = createThread([createComment({ replies_count: 1, replies: [createReply()] }), neighbour]);

		const next = removeComment(thread, { id: 1, parentCommentId: null });

		expect(next.items).toEqual([neighbour]);
		expect(next.pagination.total).toBe(1);
	});

	it('removes a reply from its root and decrements only that subtree count', () => {
		const kept = createReply({ id: 51, uuid: 'reply-2' });
		const thread = createThread([createComment({ replies_count: 2, replies: [createReply(), kept] })]);

		const next = removeComment(thread, { id: 50, parentCommentId: 1 });

		expect(next.items[0].replies).toEqual([kept]);
		expect(next.items[0].replies_count).toBe(1);
		expect(next.pagination.total).toBe(1);
	});

	it('leaves the thread alone when the target is not in the loaded page', () => {
		const thread = createThread([createComment()]);

		expect(removeComment(thread, { id: 999, parentCommentId: null })).toEqual(thread);
		expect(removeComment(thread, { id: 999, parentCommentId: 1 })).toEqual(thread);
	});

	it('patches only the edited text optimistically', () => {
		const thread = createThread([createComment({ likes_count: 3, replies: [createReply()], replies_count: 1 })]);

		const next = patchCommentContent(thread, thread.items[0].uuid, 'optimistic');

		expect(next.items[0]).toMatchObject({ content: 'optimistic', likes_count: 3, replies_count: 1 });
		expect(next.items[0].replies[0].content).toBe('a reply');
	});
});

describe('readCommentWriteError', () => {
	it.each([
		[401, 'unauthenticated'],
		[403, 'forbidden'],
		[404, 'notFound'],
	])('maps a %i problem document to %s', (status, kind) => {
		const failure = readCommentWriteError({ response: { data: { status, title: 'Nope' } } });

		expect(failure.kind).toBe(kind);
		expect(failure.message).toBe('Nope');
	});

	it('falls back to a generic message for an unrecognised failure', () => {
		expect(readCommentWriteError(new Error('boom'))).toMatchObject({ kind: 'unknown' });
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

const THREAD_KEY = getCommentsQueryKey('article', ARTICLE_UUID);

/**
 * Drives the real like seam against a real cache: only the React plumbing is
 * mocked, so the assertions below are about what actually lands in the cache.
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

		await expect(options.mutationFn('c1d7e9f0' as unknown as number, CALLBACK_CONTEXT)).rejects.toThrow();
		expect(likeLikeInstance).not.toHaveBeenCalled();
	});

	it('patches only the liked comment and leaves its neighbours alone', async () => {
		const neighbour = createComment({ id: 2, content: 'second', likes_count: 9 });
		const { queryClient, options } = useCommentLikeHarness(createThread([createComment(), neighbour]));

		await options.onMutate(1, CALLBACK_CONTEXT);

		const items = readThread(queryClient)?.items ?? [];
		expect(items[0]).toMatchObject({ id: 1, likes_count: 1, content: 'hello' });
		expect(items[0].viewer.is_liked).toBe(true);
		expect(items[1]).toEqual(neighbour);
	});

	/** Replies are likeable too, and live one level inside the cached page. */
	it('patches a liked reply inside its root', async () => {
		const thread = createThread([createComment({ replies_count: 1, replies: [createReply()] })]);
		const { queryClient, options } = useCommentLikeHarness(thread);

		await options.onMutate(50, CALLBACK_CONTEXT);

		const root = readThread(queryClient)?.items[0];
		expect(root?.replies[0]).toMatchObject({ id: 50, likes_count: 1 });
		expect(root?.replies[0].viewer.is_liked).toBe(true);
		expect(root?.likes_count).toBe(0);
	});

	it('patches the liked comment when unliking it again', async () => {
		const { queryClient, options } = useCommentLikeHarness(
			createThread([
				createComment({ likes_count: 4, viewer: { is_liked: true, can_edit: false, can_delete: false } }),
			]),
		);

		await options.onMutate(1, CALLBACK_CONTEXT);

		expect(readThread(queryClient)?.items[0]).toMatchObject({ likes_count: 3 });
		expect(readThread(queryClient)?.items[0].viewer.is_liked).toBe(false);
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

		expect(readThread(queryClient)?.items[0]).toMatchObject({ likes_count: 6 });
	});

	it('leaves the thread alone when the liked comment is not in the cached page', async () => {
		const thread = createThread([createComment({ id: 2 })]);
		const { queryClient, options } = useCommentLikeHarness(thread);

		await options.onMutate(1, CALLBACK_CONTEXT);

		expect(readThread(queryClient)).toEqual(thread);
	});
});
