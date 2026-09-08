import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { PostFormValues } from '@/components/features/community/PostForm';
import PostFormPage from './index';

const navigate = vi.fn();
const mutate = vi.fn();
let isPending = false;

let capturedSubmit: ((values: PostFormValues, meta: { dirtyKeys: never[] }) => void) | null = null;
const capturedProps: Array<Record<string, unknown>> = [];

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useNavigate: () => navigate,
	};
});

vi.mock('@/api/posts/writes', async () => {
	const actual = await vi.importActual<typeof import('@/api/posts/writes')>('@/api/posts/writes');
	return {
		...actual,
		useCreatePostMutation: () => ({ mutate, isPending }),
	};
});

vi.mock('@/components/features/community/PostForm', () => ({
	PostForm: (props: Record<string, unknown>) => {
		capturedProps.push(props);
		capturedSubmit = props.onSubmit as typeof capturedSubmit;
		return <div>PostForm</div>;
	},
}));

const draft: PostFormValues = {
	title: '  How do I read this kanji?  ',
	content: '  The second character keeps throwing me.  ',
	topic: 5,
	tags: [' howto '],
};

const render = () => renderToStaticMarkup(<PostFormPage />);

describe('PostForm route', () => {
	beforeEach(() => {
		navigate.mockClear();
		mutate.mockClear();
		capturedProps.length = 0;
		capturedSubmit = null;
		isPending = false;
	});

	it('opens on an empty draft with the first canonical topic', () => {
		expect(render()).toContain('PostForm');
		expect(capturedProps[0]).toMatchObject({
			initialValues: { title: '', content: '', topic: 1, tags: [] },
			submitLabel: 'Create Post',
		});
	});

	it('sends the trimmed StorePostRequest and navigates to the new Post by UUID', () => {
		mutate.mockImplementation((_payload, { onSuccess }) => onSuccess({ id: 12, uuid: 'post-uuid' }));

		render();
		capturedSubmit?.(draft, { dirtyKeys: [] });

		expect(mutate).toHaveBeenCalledWith(
			{
				title: 'How do I read this kanji?',
				content: 'The second character keeps throwing me.',
				topic: 5,
				tags: ['howto'],
			},
			expect.anything(),
		);
		expect(navigate).toHaveBeenCalledWith('/community/post-uuid');
	});

	// These tests render through renderToStaticMarkup, which never re-renders, so a failure is
	// asserted on the observable consequence — the author is not navigated away from their draft.
	// The rendering of the failure itself is covered by the PostForm component test.
	it('keeps the author on the form when the server rejects the write as invalid', () => {
		mutate.mockImplementation((_payload, { onError }) =>
			onError({
				response: {
					data: { status: 422, title: 'Validation failed', errors: { title: ['Title is too short.'] } },
				},
			}),
		);

		render();
		capturedSubmit?.(draft, { dirtyKeys: [] });

		expect(navigate).not.toHaveBeenCalled();
	});

	it('keeps the author on the form for a non-validation failure', () => {
		mutate.mockImplementation((_payload, { onError }) =>
			onError({ response: { data: { status: 401, title: 'Unauthenticated.' } } }),
		);

		render();
		capturedSubmit?.(draft, { dirtyKeys: [] });

		expect(navigate).not.toHaveBeenCalled();
	});

	it('marks the form as submitting so a second submit cannot start', () => {
		isPending = true;

		render();

		expect(capturedProps[0]).toMatchObject({ isSubmitting: true });
	});
});
