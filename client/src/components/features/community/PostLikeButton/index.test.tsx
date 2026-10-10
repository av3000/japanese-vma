import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useLikePostMutation } from '@/api/posts/likes';
import PostLikeButton from './index';

const navigateMock = vi.fn();
const likeMutateMock = vi.fn();
let likeIsToggling = false;
let lastToggleResult: { is_liked: boolean; likes_count: number } | undefined;
let isAuthenticatedMock = true;
const capturedButtonProps: Array<{ onClick?: () => void; disabled?: boolean }> = [];

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return { ...actual, useNavigate: () => navigateMock };
});

vi.mock('@/api/posts/likes', () => ({
	useLikePostMutation: vi.fn(() => ({
		mutate: likeMutateMock,
		data: lastToggleResult,
		isPending: likeIsToggling,
		isTogglingInstance: () => likeIsToggling,
	})),
}));

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({ isAuthenticated: isAuthenticatedMock }),
}));

vi.mock('@/components/shared/Icon', () => ({
	Icon: ({ name }: { name: string }) => <span>{name}</span>,
}));

vi.mock('@/components/shared/Button', () => ({
	Button: ({ children, onClick, disabled }: { children: ReactNode; onClick?: () => void; disabled?: boolean }) => {
		capturedButtonProps.push({ onClick, disabled });
		return (
			<button type="button" disabled={disabled}>
				{children}
			</button>
		);
	},
}));

const renderButton = (isLiked = false) =>
	renderToStaticMarkup(<PostLikeButton postId={31} detailIdentifier="post-uuid" likesCount={4} isLiked={isLiked} />);

describe('PostLikeButton', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		capturedButtonProps.length = 0;
		likeIsToggling = false;
		lastToggleResult = undefined;
		isAuthenticatedMock = true;
	});

	it('binds the toggle to the post detail cache key', () => {
		renderButton();

		expect(vi.mocked(useLikePostMutation)).toHaveBeenCalledWith('post-uuid');
	});

	it('renders the count the cached post carries in the visible label', () => {
		expect(renderButton()).toContain('Like · 4');
	});

	it('likes through the loaded numeric post id rather than the uuid route parameter', () => {
		renderButton();

		capturedButtonProps[0].onClick?.();

		expect(likeMutateMock).toHaveBeenCalledWith(31);
	});

	it('sends an anonymous reader to login instead of a like the endpoint would reject', () => {
		isAuthenticatedMock = false;
		renderButton();

		capturedButtonProps[0].onClick?.();

		expect(likeMutateMock).not.toHaveBeenCalled();
		expect(navigateMock).toHaveBeenCalledWith('/login');
	});

	it('blocks a duplicate like while the toggle is in flight', () => {
		likeIsToggling = true;
		renderButton();

		expect(capturedButtonProps[0].disabled).toBe(true);
	});

	it('shows the unfilled icon for a viewer who has not liked the post', () => {
		expect(renderButton(false)).toContain('thumbsUpRegular');
	});

	it('shows the filled icon on load for a viewer who already liked the post', () => {
		expect(renderButton(true)).toContain('thumbsUpSolid');
	});

	it('takes the pressed state from the cached post, not from the last toggle response', () => {
		lastToggleResult = { is_liked: true, likes_count: 5 };

		expect(renderButton(false)).toContain('thumbsUpRegular');
	});
});
