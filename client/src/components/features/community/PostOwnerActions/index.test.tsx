import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import PostOwnerActions from './index';

const navigate = vi.fn();
const lockMutate = vi.fn();
const deleteMutate = vi.fn();
const lockMutationForPost = vi.fn();
const deleteMutationForPost = vi.fn();

let currentUser: { id: number; isAdmin: boolean } | null = { id: 5, isAdmin: false };

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return { ...actual, useNavigate: () => navigate };
});

vi.mock('@/api/posts/writes', async () => {
	const actual = await vi.importActual<typeof import('@/api/posts/writes')>('@/api/posts/writes');
	return {
		...actual,
		useLockPostMutation: (post: unknown) => {
			lockMutationForPost(post);
			return { mutate: lockMutate, isPending: false };
		},
		useDeletePostMutation: (post: unknown) => {
			deleteMutationForPost(post);
			return { mutate: deleteMutate, isPending: false };
		},
	};
});

vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ user: currentUser, isAuthenticated: Boolean(currentUser) }) }));

const AUTHOR_ID = 5;

const props = { postId: 12, uuid: 'post-uuid', title: 'How do I read this kanji?', authorId: AUTHOR_ID };

// The edit control is a router Link, so the component needs a routing context to render at all.
const render = (overrides: Partial<typeof props> & { isLocked?: boolean } = {}) =>
	renderToStaticMarkup(
		<MemoryRouter>
			<PostOwnerActions {...props} isLocked={false} {...overrides} />
		</MemoryRouter>,
	);

describe('PostOwnerActions', () => {
	beforeEach(() => {
		navigate.mockClear();
		lockMutate.mockClear();
		deleteMutate.mockClear();
		lockMutationForPost.mockClear();
		deleteMutationForPost.mockClear();
		currentUser = { id: AUTHOR_ID, isAdmin: false };
	});

	it('renders nothing for a guest or an unrelated reader', () => {
		currentUser = null;
		expect(render()).toBe('');

		currentUser = { id: 42, isAdmin: false };
		expect(render()).toBe('');
	});

	it('gives the author edit and delete, but not lock', () => {
		const html = render();

		expect(html).toContain('Edit this post');
		expect(html).toContain('Delete this post');
		expect(html).not.toContain('Lock this post');
	});

	it('gives an admin lock and delete, but not edit', () => {
		currentUser = { id: 9, isAdmin: true };

		const html = render();

		expect(html).toContain('Lock this post');
		// PostPolicy::canDelete is owner *or* admin; the previous shim hid this from admins.
		expect(html).toContain('Delete this post');
		expect(html).not.toContain('Edit this post');
	});

	it('links edit to the UUID, not the transitional numeric id', () => {
		const html = render();

		expect(html).toContain('/community/edit/post-uuid');
		expect(html).not.toContain('/community/edit/12');
	});

	it('labels the lock control from the current state', () => {
		currentUser = { id: 9, isAdmin: true };

		expect(render({ isLocked: true })).toContain('Unlock this post');
		expect(render({ isLocked: false })).toContain('Lock this post');
	});

	it('addresses both mutations by the UUID with the numeric id for cache reconciliation', () => {
		currentUser = { id: 9, isAdmin: true };

		render();

		expect(lockMutationForPost).toHaveBeenCalledWith({ id: 12, uuid: 'post-uuid' });
		expect(deleteMutationForPost).toHaveBeenCalledWith({ id: 12, uuid: 'post-uuid' });
	});

	it('uses no legacy endpoint literal', () => {
		expect(render()).not.toContain('/api/post');
	});
});
