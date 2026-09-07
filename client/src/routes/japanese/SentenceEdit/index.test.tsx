import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { SentenceFormValues } from '@/components/features/japanese/sentence/SentenceForm';
import SentenceEditPage from './index';

const navigate = vi.fn();
const mutate = vi.fn();
const useSentenceQueryMock = vi.fn();
const updateMutationForUuid = vi.fn();

let currentUser: { id: number; isAdmin: boolean } | null = { id: 5, isAdmin: false };
let capturedSubmit: ((values: SentenceFormValues) => void) | null = null;
const capturedProps: Array<Record<string, unknown>> = [];

// Route param is the legacy numeric id on purpose: the detail endpoint accepts it,
// but the update endpoint only accepts the UUID.
vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useNavigate: () => navigate,
		useParams: () => ({ sentence_id: '77' }),
	};
});

vi.mock('@/api/sentences/details', () => ({
	useSentenceQuery: (...args: unknown[]) => useSentenceQueryMock(...args),
}));

vi.mock('@/api/sentences/authoring', async () => {
	const actual = await vi.importActual<typeof import('@/api/sentences/authoring')>('@/api/sentences/authoring');
	return {
		...actual,
		useUpdateSentenceMutation: (uuid: string) => {
			updateMutationForUuid(uuid);
			return { mutate, isPending: false };
		},
	};
});

vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ user: currentUser, isAuthenticated: Boolean(currentUser) }) }));

vi.mock('@/components/features/japanese/sentence/SentenceForm', () => ({
	SentenceForm: (props: Record<string, unknown>) => {
		capturedProps.push(props);
		capturedSubmit = props.onSubmit as (values: SentenceFormValues) => void;
		return <div>SentenceForm</div>;
	},
}));

const sentence = {
	id: 77,
	uuid: 'sentence-uuid',
	user_id: 5,
	tatoeba_entry: null,
	content: '水を飲みます。',
	kanjis: [],
};

const render = () => renderToStaticMarkup(<SentenceEditPage />);

describe('SentenceEdit', () => {
	beforeEach(() => {
		navigate.mockClear();
		mutate.mockClear();
		updateMutationForUuid.mockClear();
		capturedProps.length = 0;
		capturedSubmit = null;
		currentUser = { id: 5, isAdmin: false };
		useSentenceQueryMock.mockReturnValue({ data: sentence, isLoading: false, isError: false });
	});

	it('renders the loading state while the detail query resolves', () => {
		useSentenceQueryMock.mockReturnValue({ isLoading: true, isError: false });

		expect(render()).toContain('data-loading-family="form"');
	});

	it('renders a load failure when the sentence is missing', () => {
		useSentenceQueryMock.mockReturnValue({ isLoading: false, isError: true });

		expect(render()).toContain('Sentence could not be loaded.');
	});

	it('refuses imported sentences, including for an admin', () => {
		currentUser = { id: 9, isAdmin: true };
		useSentenceQueryMock.mockReturnValue({
			data: { ...sentence, user_id: null },
			isLoading: false,
			isError: false,
		});

		const html = render();

		expect(html).toContain('Imported sentences cannot be edited.');
		expect(html).not.toContain('SentenceForm');
	});

	it('refuses a viewer who is neither the author nor an admin', () => {
		currentUser = { id: 42, isAdmin: false };

		const html = render();

		expect(html).toContain('You do not have permission to edit this sentence.');
		expect(html).not.toContain('SentenceForm');
	});

	it('initialises the form from the loaded sentence for the author', () => {
		const html = render();

		expect(html).toContain('SentenceForm');
		expect(capturedProps[0]).toMatchObject({
			initialValues: { content: '水を飲みます。' },
			submitLabel: 'Update',
			disableSubmitWhenUnchanged: true,
		});
	});

	it('updates through the UUID even though the route carried the numeric id', () => {
		mutate.mockImplementation((_payload, { onSuccess }) => onSuccess({ uuid: 'sentence-uuid', id: 77 }));

		render();
		capturedSubmit?.({ content: '火を見ます。' });

		expect(useSentenceQueryMock).toHaveBeenCalledWith('77');
		expect(updateMutationForUuid).toHaveBeenCalledWith('sentence-uuid');
		expect(mutate).toHaveBeenCalledWith({ content: '火を見ます。' }, expect.anything());
		expect(navigate).toHaveBeenCalledWith('/sentence/sentence-uuid');
	});

	it('does not navigate when the server rejects the write', () => {
		mutate.mockImplementation((_payload, { onError }) =>
			onError({ response: { data: { status: 403, title: 'You do not have permission.' } } }),
		);

		render();
		capturedSubmit?.({ content: '火を見ます。' });

		expect(navigate).not.toHaveBeenCalled();
	});
});
