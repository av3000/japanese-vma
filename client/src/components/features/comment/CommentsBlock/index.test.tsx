import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { getCommentsQueryKey, useLikeCommentMutation } from '@/api/comments';
import type { CommentListResource } from '@/api/generated/model/commentListResource';
import type { CommentResource } from '@/api/generated/model/commentResource';
import { ObjectTemplateType } from '@/shared/constants/enums';
import CommentsBlock from './index';

const ARTICLE_UUID = '0fb383ad-e203-43f3-9c15-a34bd1ad1a46';

const likeMutateMock = vi.fn();
const fetchNextPageMock = vi.fn().mockResolvedValue(undefined);
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
		// The thread query merges its pages through `select`, so the mock hands back a merged thread.
		useInfiniteQuery: vi.fn((options: any) => {
			capturedQueryOptions.push(options);
			return {
				...queryState,
				hasNextPage: queryState.data?.pagination.has_more ?? false,
				fetchNextPage: fetchNextPageMock,
				isFetchingNextPage: false,
				isFetchNextPageError: false,
			};
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

	it('starts the thread at page 1 and asks for the next page only while there is one', () => {
		render();

		const options = capturedQueryOptions[0] as unknown as {
			initialPageParam: number;
			getNextPageParam: (lastPage: ReturnType<typeof thread>) => number | undefined;
		};

		expect(options.initialPageParam).toBe(1);
		expect(options.getNextPageParam(thread([comment()], { page: 1, has_more: true }))).toBe(2);
		expect(options.getNextPageParam(thread([comment()], { page: 2, has_more: false }))).toBeUndefined();
	});

	it('loads the next page when the list asks to show more', () => {
		queryState = { data: thread([comment()], { total: 42, has_more: true }), isLoading: false, isError: false };

		render();
		capturedListProps[0].onShowMore();

		expect(fetchNextPageMock).toHaveBeenCalledTimes(1);
	});

	it('puts the count heading one level under the page heading unless told otherwise', () => {
		render();
		render({ headingLevel: 2 });

		expect(capturedListProps[0].headingLevel).toBe(3);
		expect(capturedListProps[1].headingLevel).toBe(2);
	});

	it('prompts in plain text rather than headings', () => {
		const html = render();

		expect(html).toContain('<p class=');
		expect(html).toContain('Share what&#x27;s on your mind');
		expect(html).not.toMatch(/<h[1-6]/);
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

	// The backend closes a locked parent to new comments only; CommentPolicy never
	// consults the lock. Withholding edit or delete here would strand an author on
	// their own words, so the lock must not reach past the write affordances.
	it('keeps editing and deleting available on a locked parent', async () => {
		render({ isLocked: true });

		await capturedListProps[0].onEdit(comment(), 'edited');
		expect(updateMutateAsync).toHaveBeenCalledWith({ uuid: 'comment-11', content: 'edited' });

		capturedListProps[0].onDelete(comment());
		expect(deleteMutateAsync).toHaveBeenCalledWith({
			id: 11,
			uuid: 'comment-11',
			parentCommentId: null,
		});
	});

	// Post and Sentence share one mapping; the parent name is the only thing that
	// differs between them, and it has to select the read key on its own.
	it('reads a sentence thread through the same seam as an article', () => {
		const sentenceUuid = '2b0b3a3f-5d19-4a23-9e0a-3a5f6a1c9e77';

		renderToStaticMarkup(<CommentsBlock parent="sentence" entityId={77} entityUuid={sentenceUuid} />);

		expect(capturedQueryOptions[0].queryKey).toEqual(getCommentsQueryKey('sentence', sentenceUuid));
		expect(capturedQueryOptions[0].queryKey).not.toEqual(getCommentsQueryKey('article', sentenceUuid));
	});

	it('prompts an anonymous reader to log in and offers no reply affordance', () => {
		isAuthenticated = false;

		const markup = render();

		expect(markup).toContain('login');
		expect(capturedFormProps).toHaveLength(0);
		expect(capturedListProps[0].canReply).toBe(false);
	});
});

describe('CommentList', () => {
	const idle = () => ({
		isLikePending: false,
		isEditPending: false,
		isDeletePending: false,
		isReplyPending: false,
	});

	// The block's tests mock CommentList; these render the real one, imported once up front so the
	// first test does not pay for the import inside its time limit.
	let CommentList: typeof import('./CommentList/CommentList').default;

	beforeAll(async () => {
		({ default: CommentList } =
			await vi.importActual<typeof import('./CommentList/CommentList')>('./CommentList/CommentList'));
	});

	const renderList = async (props: Record<string, unknown> = {}) => {
		return renderToStaticMarkup(
			<QueryClientProvider client={new QueryClient()}>
				<CommentList
					comments={[comment()]}
					total={12}
					hasMore={false}
					canReply={false}
					stateFor={idle}
					onLike={vi.fn()}
					onDelete={vi.fn()}
					onEdit={vi.fn()}
					onReply={vi.fn()}
					{...props}
				/>
			</QueryClientProvider>,
		);
	};

	it('names the count in its heading, at the level it is given, with no h5 or h6', async () => {
		const byDefault = await renderList();
		const underH1 = await renderList({ headingLevel: 2 });

		expect(byDefault).toMatch(/<h3[^>]*>12 comments<\/h3>/);
		expect(underH1).toMatch(/<h2[^>]*>12 comments<\/h2>/);
		expect(byDefault + underH1).not.toMatch(/<h[56]/);
	});

	it('says "1 comment" for one', async () => {
		expect(await renderList({ total: 1 })).toContain('>1 comment</h3>');
	});

	it('offers Show more only while the server has more', async () => {
		expect(await renderList()).not.toContain('Show more comments');

		const more = await renderList({ total: 200, hasMore: true });

		expect(more).toContain('Showing 1 of 200 comments');
		expect(more).toContain('Show more comments');
	});

	it('says when the next page could not be loaded and keeps the button to retry', async () => {
		const failed = await renderList({ total: 200, hasMore: true, loadMoreFailed: true });

		expect(failed).toContain('More comments could not be loaded');
		expect(failed).toContain('Show more comments');
	});
});
