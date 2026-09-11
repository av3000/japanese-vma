import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { getCommentsQueryKey, useLikeCommentMutation } from '@/api/comments';
import type { CommentListResource } from '@/api/generated/model/commentListResource';
import type { CommentResource } from '@/api/generated/model/commentResource';
import { ObjectTemplateType } from '@/shared/constants/enums';
import CommentsBlock from './index';

const ARTICLE_UUID = '0fb383ad-e203-43f3-9c15-a34bd1ad1a46';

const likeMutateMock = vi.fn();
const createMutateAsync = vi.fn().mockResolvedValue(undefined);
const updateMutateAsync = vi.fn().mockResolvedValue(undefined);
const deleteMutateAsync = vi.fn().mockResolvedValue(undefined);

let togglingCommentId: number | null = null;
let queryState: { data?: CommentListResource; isLoading: boolean; isError: boolean } = {
	data: undefined,
	isLoading: false,
	isError: false,
};
let isAuthenticated = true;

const capturedListProps: Array<Record<string, any>> = [];
const capturedFormProps: Array<Record<string, any>> = [];
const capturedQueryOptions: Array<{ queryKey: unknown }> = [];

const comment = (overrides: Partial<CommentResource> = {}): CommentResource => ({
	id: 11,
	uuid: 'comment-11',
	entity_uuid: ARTICLE_UUID,
	entity_type_uuid: ObjectTemplateType.ARTICLE,
	entity_type_label: 'Article',
	author: { id: 7, name: 'Aki', uuid: 'author-uuid' },
	content: 'hello',
	parent_comment_id: null,
	is_reply: false,
	likes_count: 0,
	viewer: { is_liked: false, can_edit: true, can_delete: true },
	replies_count: 0,
	replies: [],
	created_at: '2026-05-04T10:00:00Z',
	updated_at: '2026-05-04T10:00:00Z',
	...overrides,
});

const thread = (items: CommentResource[], overrides: Partial<CommentListResource['pagination']> = {}) => ({
	items,
	pagination: { page: 1, per_page: 20, total: items.length, last_page: 1, has_more: false, ...overrides },
});

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
	};
});

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
	return {
		...actual,
		useQuery: vi.fn((options: any) => {
			capturedQueryOptions.push(options);
			return queryState;
		}),
	};
});

vi.mock('@/api/comments', async () => {
	const actual = await vi.importActual<typeof import('@/api/comments')>('@/api/comments');
	return {
		...actual,
		useCreateCommentMutation: vi.fn(() => ({
			mutateAsync: createMutateAsync,
			isPending: false,
			variables: undefined,
		})),
		useUpdateCommentMutation: vi.fn(() => ({
			mutateAsync: updateMutateAsync,
			isPending: false,
			variables: undefined,
		})),
		useDeleteCommentMutation: vi.fn(() => ({
			mutateAsync: deleteMutateAsync,
			isPending: false,
			variables: undefined,
		})),
		useLikeCommentMutation: vi.fn(() => ({
			mutate: likeMutateMock,
			isPending: togglingCommentId !== null,
			isTogglingInstance: (commentId: number) => togglingCommentId === commentId,
		})),
	};
});

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({ user: isAuthenticated ? { id: 7, name: 'Aki' } : null, isAuthenticated }),
}));

vi.mock('./CommentForm/CommentForm', () => ({
	default: (props: Record<string, any>) => {
		capturedFormProps.push(props);
		return <form />;
	},
}));

vi.mock('./CommentList/CommentList', () => ({
	default: (props: Record<string, any>) => {
		capturedListProps.push(props);
		return <div>Comment list</div>;
	},
}));

const render = (props: Partial<Parameters<typeof CommentsBlock>[0]> = {}) =>
	renderToStaticMarkup(<CommentsBlock parent="article" entityId={101} entityUuid={ARTICLE_UUID} {...props} />);

describe('CommentsBlock', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		capturedListProps.length = 0;
		capturedFormProps.length = 0;
		capturedQueryOptions.length = 0;
		togglingCommentId = null;
		isAuthenticated = true;
		queryState = { data: thread([comment()]), isLoading: false, isError: false };
	});

	it('binds the like mutation to the same thread key the comment query reads', () => {
		render();

		expect(capturedQueryOptions[0].queryKey).toEqual(getCommentsQueryKey('article', ARTICLE_UUID));
		expect(vi.mocked(useLikeCommentMutation)).toHaveBeenCalledWith(getCommentsQueryKey('article', ARTICLE_UUID));
	});

	it('renders a loading state before the thread arrives', () => {
		queryState = { data: undefined, isLoading: true, isError: false };

		expect(render()).toContain('Loading comments');
		expect(capturedListProps).toHaveLength(0);
	});

	it('renders a failure state instead of an empty thread when the read fails', () => {
		queryState = { data: undefined, isLoading: false, isError: true };

		expect(render()).toContain('Comments could not be loaded');
		expect(capturedListProps).toHaveLength(0);
	});

	/** The badge used to render the loaded page size while claiming to be the total. */
	it('reports the thread total rather than the loaded page size', () => {
		queryState = { data: thread([comment()], { total: 42, has_more: true }), isLoading: false, isError: false };

		render();

		expect(capturedListProps[0].total).toBe(42);
		expect(capturedListProps[0].hasMore).toBe(true);
	});

	it('renders an empty thread without error', () => {
		queryState = { data: thread([]), isLoading: false, isError: false };

		render();

		expect(capturedListProps[0].comments).toEqual([]);
		expect(capturedListProps[0].total).toBe(0);
	});

	it('likes a comment through its loaded numeric id', () => {
		render();

		capturedListProps[0].onLike(11);

		expect(likeMutateMock).toHaveBeenCalledWith(11);
	});

	it('reports pending state per comment so one like does not disable the thread', () => {
		togglingCommentId = 11;
		render();

		expect(capturedListProps[0].stateFor(11).isLikePending).toBe(true);
		expect(capturedListProps[0].stateFor(12).isLikePending).toBe(false);
	});

	it('deletes through the comment uuid while telling the cache which branch it was in', async () => {
		render();

		await capturedListProps[0].onDelete(comment({ id: 11, uuid: 'comment-11', parent_comment_id: null }));

		expect(deleteMutateAsync).toHaveBeenCalledWith({ id: 11, uuid: 'comment-11', parentCommentId: null });
	});

	it('edits through the comment uuid', async () => {
		render();

		await capturedListProps[0].onEdit(comment({ uuid: 'comment-11' }), 'edited');

		expect(updateMutateAsync).toHaveBeenCalledWith({ uuid: 'comment-11', content: 'edited' });
	});

	it('sends parent_comment_id when replying', async () => {
		render();

		await capturedListProps[0].onReply(11, 'a reply');

		expect(createMutateAsync).toHaveBeenCalledWith({ content: 'a reply', parent_comment_id: 11 });
	});

	it('omits parent_comment_id for a new top-level comment', async () => {
		render();

		await capturedFormProps[0].onSubmit('brand new');

		expect(createMutateAsync).toHaveBeenCalledWith({ content: 'brand new' });
	});

	it('offers the composer its own pending flag, not the thread query state', () => {
		render();

		expect(capturedFormProps[0]).toHaveProperty('isSubmitting', false);
		expect(capturedFormProps[0]).not.toHaveProperty('isLoading');
	});

	it('hides the composer and reply affordance on a locked parent but keeps the thread readable', () => {
		const markup = render({ isLocked: true });

		expect(markup).toContain('locked');
		expect(capturedFormProps).toHaveLength(0);
		expect(capturedListProps[0].canReply).toBe(false);
	});

	it('prompts an anonymous reader to log in and offers no reply affordance', () => {
		isAuthenticated = false;

		const markup = render();

		expect(markup).toContain('login');
		expect(capturedFormProps).toHaveLength(0);
		expect(capturedListProps[0].canReply).toBe(false);
	});
});
