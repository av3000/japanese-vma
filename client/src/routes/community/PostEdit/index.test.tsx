import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { PostFormField, PostFormValues } from '@/components/features/community/PostForm';
import PostEditPage from './index';

const navigate = vi.fn();
const mutate = vi.fn();
const usePostQueryMock = vi.fn();
const updateMutationForUuid = vi.fn();

let currentUser: { id: number; isAdmin: boolean } | null = { id: 5, isAdmin: false };
let capturedSubmit: ((values: PostFormValues, meta: { dirtyKeys: PostFormField[] }) => void) | null = null;
const capturedProps: Array<Record<string, unknown>> = [];

// The route param is the transitional numeric id on purpose: the detail endpoint resolves it, but
// the update endpoint only accepts the UUID.
vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useNavigate: () => navigate,
		useParams: () => ({ post_id: '12' }),
	};
});

vi.mock('@/api/posts/reads', async () => {
	const actual = await vi.importActual<typeof import('@/api/posts/reads')>('@/api/posts/reads');
	return { ...actual, usePostQuery: (...args: unknown[]) => usePostQueryMock(...args) };
});

vi.mock('@/api/posts/writes', async () => {
	const actual = await vi.importActual<typeof import('@/api/posts/writes')>('@/api/posts/writes');
	return {
		...actual,
		useUpdatePostMutation: (uuid: string) => {
			updateMutationForUuid(uuid);
			return { mutate, isPending: false };
		},
	};
});

vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ user: currentUser, isAuthenticated: Boolean(currentUser) }) }));

vi.mock('@/components/features/community/PostForm', () => ({
	PostForm: (props: Record<string, unknown>) => {
		capturedProps.push(props);
		capturedSubmit = props.onSubmit as typeof capturedSubmit;
		return <div>PostForm</div>;
	},
}));

const post = {
	id: 12,
	uuid: 'post-uuid',
	title: 'How do I read this kanji?',
	content: 'The second character keeps throwing me.',
	topic: 5,
	topic_label: 'Bug',
	locked: false,
	author: { id: 5, name: 'Author' },
	hashtags: [
		{ id: 1, content: 'howto' },
		{ id: 2, content: 'kanji' },
	],
};

const edited: PostFormValues = { ...post, topic: 3, tags: ['howto'] } as unknown as PostFormValues;

const render = () => renderToStaticMarkup(<PostEditPage />);

describe('PostEdit', () => {
	beforeEach(() => {
		navigate.mockClear();
		mutate.mockClear();
		updateMutationForUuid.mockClear();
		capturedProps.length = 0;
		capturedSubmit = null;
		currentUser = { id: 5, isAdmin: false };
		usePostQueryMock.mockReturnValue({ data: post, isLoading: false, isError: false });
	});

	it('renders the form loader while the detail query resolves', () => {
		usePostQueryMock.mockReturnValue({ isLoading: true, isError: false });

		expect(render()).toContain('data-loading-family="form"');
	});

	it('renders a load failure when the Post is missing', () => {
		usePostQueryMock.mockReturnValue({ isLoading: false, isError: true });

		expect(render()).toContain('Post could not be loaded.');
	});

	it('initialises the form from the cached detail, mapping hashtags onto tags', () => {
		const html = render();

		expect(html).toContain('PostForm');
		expect(usePostQueryMock).toHaveBeenCalledWith('12');
		expect(capturedProps[0]).toMatchObject({
			initialValues: {
				title: 'How do I read this kanji?',
				content: 'The second character keeps throwing me.',
				topic: 5,
				tags: ['howto', 'kanji'],
			},
			submitLabel: 'Update Post',
			disableSubmitWhenUnchanged: true,
		});
	});

	it('falls back to a renderable topic when a legacy Post carries an unknown code', () => {
		usePostQueryMock.mockReturnValue({ data: { ...post, topic: 99 }, isLoading: false, isError: false });

		render();

		expect(capturedProps[0]).toMatchObject({ initialValues: { topic: 1 } });
	});

	it('refuses a viewer who is not the author', () => {
		currentUser = { id: 42, isAdmin: false };

		const html = render();

		expect(html).toContain('You do not have permission to edit this post.');
		expect(html).not.toContain('PostForm');
	});

	it('refuses an admin, because editing is owner-only under PostPolicy::canUpdate', () => {
		currentUser = { id: 9, isAdmin: true };

		const html = render();

		expect(html).toContain('You do not have permission to edit this post.');
		expect(html).not.toContain('PostForm');
	});

	it('refuses a guest', () => {
		currentUser = null;

		expect(render()).toContain('You do not have permission to edit this post.');
	});

	it('sends only the dirty fields to the UUID even though the route carried the numeric id', () => {
		mutate.mockImplementation((_payload, { onSuccess }) => onSuccess({ id: 12, uuid: 'post-uuid' }));

		render();
		capturedSubmit?.(edited, { dirtyKeys: ['topic', 'tags'] });

		expect(updateMutationForUuid).toHaveBeenCalledWith('post-uuid');
		expect(mutate).toHaveBeenCalledWith({ topic: 3, tags: ['howto'] }, expect.anything());
		expect(navigate).toHaveBeenCalledWith('/community/post-uuid');
	});

	it('sends an empty tags array when the author cleared every tag', () => {
		mutate.mockImplementation((_payload, { onSuccess }) => onSuccess({ id: 12, uuid: 'post-uuid' }));

		render();
		capturedSubmit?.({ ...edited, tags: [] }, { dirtyKeys: ['tags'] });

		expect(mutate).toHaveBeenCalledWith({ tags: [] }, expect.anything());
	});

	it('returns to the Post instead of sending an empty body the server would reject', () => {
		render();
		capturedSubmit?.(edited, { dirtyKeys: [] });

		expect(mutate).not.toHaveBeenCalled();
		expect(navigate).toHaveBeenCalledWith('/community/post-uuid');
	});

	it('does not navigate away from the draft when the server rejects the write', () => {
		mutate.mockImplementation((_payload, { onError }) =>
			onError({ response: { data: { status: 403, title: 'This post is not yours.' } } }),
		);

		render();
		capturedSubmit?.(edited, { dirtyKeys: ['title'] });

		expect(navigate).not.toHaveBeenCalled();
	});
});
