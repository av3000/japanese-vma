import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { getCommentsQueryKey, useLikeCommentMutation } from '@/api/comments';
import { ObjectTemplateType } from '@/shared/constants/enums';
import CommentsBlock from './index';

const likeMutateMock = vi.fn();
let togglingCommentId: number | null = null;
const capturedCommentListProps: Array<{
	onLike: (commentId: number) => void;
	onDelete: (commentId: number) => void;
	isLikePending: (commentId: number) => boolean;
}> = [];
const capturedQueryOptions: Array<{ queryKey: unknown }> = [];

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
			return { data: [{ id: 11 }, { id: 12 }], isLoading: false };
		}),
		useMutation: vi.fn(() => ({ mutate: vi.fn(), mutateAsync: vi.fn(), isPending: false })),
		useQueryClient: vi.fn(() => ({ setQueryData: vi.fn(), getQueryData: vi.fn(), cancelQueries: vi.fn() })),
	};
});

vi.mock('@/api/comments', async () => {
	const actual = await vi.importActual<typeof import('@/api/comments')>('@/api/comments');
	return {
		...actual,
		fetchComments: vi.fn(),
		addComment: vi.fn(),
		deleteComment: vi.fn(),
		useLikeCommentMutation: vi.fn(() => ({
			mutate: likeMutateMock,
			isPending: togglingCommentId !== null,
			isTogglingInstance: (commentId: number) => togglingCommentId === commentId,
		})),
	};
});

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({ user: { id: 7, name: 'Aki' }, isAuthenticated: true }),
}));

vi.mock('./CommentForm/CommentForm', () => ({
	default: () => <form />,
}));

vi.mock('./CommentList/CommentList', () => ({
	default: (props: (typeof capturedCommentListProps)[number]) => {
		capturedCommentListProps.push(props);
		return <div>Comment list</div>;
	},
}));

const renderCommentsBlock = () =>
	renderToStaticMarkup(
		<CommentsBlock
			readObjectType="article"
			readObjectUuid="article-uuid"
			entityId={101}
			entityType={ObjectTemplateType.ARTICLE}
			entityUuid="article-uuid"
		/>,
	);

describe('CommentsBlock likes', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		capturedCommentListProps.length = 0;
		capturedQueryOptions.length = 0;
		togglingCommentId = null;
	});

	it('binds the like mutation to the same thread key the comment query reads', () => {
		renderCommentsBlock();

		expect(capturedQueryOptions[0].queryKey).toEqual(getCommentsQueryKey('article', 101));
		expect(vi.mocked(useLikeCommentMutation)).toHaveBeenCalledWith(getCommentsQueryKey('article', 101));
	});

	it('likes a comment through its loaded numeric id', () => {
		renderCommentsBlock();

		capturedCommentListProps[0].onLike(11);

		expect(likeMutateMock).toHaveBeenCalledWith(11);
	});

	it('reports pending state per comment so one like does not disable the whole thread', () => {
		togglingCommentId = 11;
		renderCommentsBlock();

		expect(capturedCommentListProps[0].isLikePending(11)).toBe(true);
		expect(capturedCommentListProps[0].isLikePending(12)).toBe(false);
	});

	it('reports nothing pending when no like is in flight', () => {
		renderCommentsBlock();

		expect(capturedCommentListProps[0].isLikePending(11)).toBe(false);
	});
});
