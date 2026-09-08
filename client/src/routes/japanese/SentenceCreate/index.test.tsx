import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { SentenceFormValues } from '@/components/features/japanese/sentence/SentenceForm';
import SentenceCreatePage from './index';

const navigate = vi.fn();
const mutate = vi.fn();
let isPending = false;

// The form is stubbed so the test can drive submission: Vitest runs in the node
// environment here (no jsdom), so there is no way to type into or click the real one.
let capturedSubmit: ((values: SentenceFormValues) => void) | null = null;
const capturedProps: Array<Record<string, unknown>> = [];

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useNavigate: () => navigate,
	};
});

vi.mock('@/api/sentences/authoring', async () => {
	const actual = await vi.importActual<typeof import('@/api/sentences/authoring')>('@/api/sentences/authoring');
	return {
		...actual,
		useCreateSentenceMutation: () => ({ mutate, isPending }),
	};
});

vi.mock('@/components/features/japanese/sentence/SentenceForm', () => ({
	SentenceForm: (props: Record<string, unknown>) => {
		capturedProps.push(props);
		capturedSubmit = props.onSubmit as (values: SentenceFormValues) => void;
		return <div>SentenceForm</div>;
	},
}));

const render = () => renderToStaticMarkup(<SentenceCreatePage />);

describe('SentenceCreate', () => {
	beforeEach(() => {
		navigate.mockClear();
		mutate.mockClear();
		capturedProps.length = 0;
		capturedSubmit = null;
		isPending = false;
	});

	it('starts from an empty form', () => {
		render();

		expect(capturedProps[0]).toMatchObject({ initialValues: { content: '' }, submitLabel: 'Create' });
	});

	it('sends a trimmed payload and navigates to the UUID the server returned', () => {
		mutate.mockImplementation((_payload, { onSuccess }) => onSuccess({ uuid: 'created-uuid', id: 9 }));

		render();
		capturedSubmit?.({ content: '  水を飲みます。  ' });

		expect(mutate).toHaveBeenCalledWith({ content: '水を飲みます。' }, expect.anything());
		expect(navigate).toHaveBeenCalledWith('/sentence/created-uuid');
	});

	it('passes field errors to the form and does not navigate on a 422', () => {
		mutate.mockImplementation((_payload, { onError }) =>
			onError({ response: { data: { status: 422, title: 'Validation failed', errors: { content: ['Too short.'] } } } }),
		);

		render();
		capturedSubmit?.({ content: 'あいう' });

		expect(navigate).not.toHaveBeenCalled();
	});

	it('marks the form as submitting while the write is in flight', () => {
		isPending = true;

		render();

		expect(capturedProps[0]).toMatchObject({ isSubmitting: true });
	});
});
