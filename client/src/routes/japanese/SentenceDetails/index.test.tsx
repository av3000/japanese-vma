/**
 * @vitest-environment jsdom
 */
import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { AxiosError, AxiosHeaders } from 'axios';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { sentenceLong, sentenceShort } from '@/components/features/japanese/related/dictionaryDetail.fixtures';
import { SavedListType } from '@/shared/constants/enums';
import { renderWithAct } from '@/test/renderWithAct';
import SentenceDetails from './index';

const useSentenceQueryMock = vi.fn();
const authorizedWidgetProps: Array<Record<string, unknown>> = [];
const commentsBlockProps: Array<Record<string, unknown>> = [];
const deleteMutate = vi.fn();
const navigate = vi.fn();

let currentUser: { id: number; isAdmin: boolean } | null = null;

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to, className }: { children: ReactNode; to: string; className?: string }) => (
			<a href={to} className={className}>
				{children}
			</a>
		),
		useParams: () => ({ sentence_id: 'sentence-route-uuid' }),
		useNavigate: () => navigate,
	};
});

vi.mock('@/api/sentences/details', () => ({
	useSentenceQuery: (...args: unknown[]) => useSentenceQueryMock(...args),
}));
vi.mock('@/api/sentences/authoring', async () => {
	const actual = await vi.importActual<typeof import('@/api/sentences/authoring')>('@/api/sentences/authoring');
	return {
		...actual,
		useDeleteSentenceMutation: () => ({ mutate: deleteMutate, isPending: false }),
	};
});
vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({ isAuthenticated: currentUser !== null, user: currentUser }),
}));
vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));
vi.mock('@/components/features/catalogues/AuthorizedBookmarkWidget', () => ({
	AuthorizedBookmarkWidget: (props: Record<string, unknown>) => {
		authorizedWidgetProps.push(props);
		return <div>Bookmark</div>;
	},
}));
vi.mock('@/components/features/comment/CommentsBlock', () => ({
	default: (props: Record<string, unknown>) => {
		commentsBlockProps.push(props);
		return <div>Comments</div>;
	},
}));

const loaded = (data: unknown) => ({ data, isLoading: false, isError: false, error: null });
const memberSentence = {
	...sentenceShort,
	user_id: 5,
	author: { id: 5, name: 'Kenji', uuid: 'user-kenji' },
	tatoeba_entry: null,
};

const button = (root: ParentNode, name: string) =>
	Array.from(root.querySelectorAll('button')).find(
		(element) => (element.getAttribute('aria-label') ?? element.textContent ?? '').trim() === name,
	);

describe('SentenceDetails', () => {
	beforeEach(() => {
		authorizedWidgetProps.length = 0;
		commentsBlockProps.length = 0;
		deleteMutate.mockReset();
		navigate.mockClear();
		currentUser = null;
		useSentenceQueryMock.mockReturnValue(loaded(sentenceShort));
		HTMLDialogElement.prototype.showModal = vi.fn(function (this: HTMLDialogElement) {
			this.setAttribute('open', '');
		});
		HTMLDialogElement.prototype.close = vi.fn(function (this: HTMLDialogElement) {
			this.removeAttribute('open');
		});
	});

	afterEach(() => {
		document.body.innerHTML = '';
	});

	it('shows the sentence as the title, links its Tatoeba entry and lists its kanji', () => {
		const html = renderToStaticMarkup(<SentenceDetails />);

		expect(useSentenceQueryMock).toHaveBeenCalledWith('sentence-route-uuid');
		expect(html).toMatch(/<h1 class="[^"]*sentence[^"]*" lang="ja">親友は何人いる？<\/h1>/);
		expect(html).toContain('href="https://tatoeba.org/eng/sentences/show/4777"');
		expect(html).toContain('Tatoeba #4777');
		expect(html).toMatch(/>Characters<\/dt><dd[^>]*>8<\/dd>/);
		expect(html).toMatch(/>Source<\/dt><dd[^>]*>Tatoeba<\/dd>/);
		expect(html).toContain('>Kanji in this sentence');
		expect(html).toContain(`/kanji/${sentenceShort.kanjis[0].uuid}`);
		expect(html).toMatch(/<a href="\/sentences"[^>]*>.*Sentences<\/a>/);
	});

	it('keeps a 302-character sentence as one title with all 59 kanji', () => {
		useSentenceQueryMock.mockReturnValue(loaded(sentenceLong));

		const html = renderToStaticMarkup(<SentenceDetails />);

		expect(html.match(/<h1/g)).toHaveLength(1);
		expect(html).toMatch(/>Kanji<\/dt><dd[^>]*>59<\/dd>/);
	});

	it('composes comments through the shared seam, with the response id rather than the route', () => {
		renderToStaticMarkup(<SentenceDetails />);

		expect(commentsBlockProps).toEqual([
			{ parent: 'sentence', entityId: sentenceShort.id, entityUuid: sentenceShort.uuid },
		]);
	});

	it('offers a labelled Save to signed-in viewers, by the response id', () => {
		expect(renderToStaticMarkup(<SentenceDetails />)).not.toContain('Bookmark');

		currentUser = { id: 42, isAdmin: false };
		renderToStaticMarkup(<SentenceDetails />);
		expect(authorizedWidgetProps[0]).toMatchObject({
			entityId: sentenceShort.id,
			instanceObjectType: SavedListType.SENTENCES,
			isKnownType: SavedListType.KNOWNSENTENCES,
			label: 'Save to a catalogue',
		});
	});

	it('offers edit and delete to the author, crediting the sentence to them', () => {
		currentUser = { id: 5, isAdmin: false };
		useSentenceQueryMock.mockReturnValue(loaded(memberSentence));

		const html = renderToStaticMarkup(<SentenceDetails />);

		expect(html).toContain('>Your sentence</h2>');
		expect(html).toContain(`href="/sentences/${sentenceShort.uuid}/edit"`);
		expect(html).toContain('Delete sentence');
		expect(html).toContain('>Kenji</strong>');
	});

	it('hides both controls from a viewer who does not own the sentence', () => {
		currentUser = { id: 42, isAdmin: false };
		useSentenceQueryMock.mockReturnValue(loaded(memberSentence));

		const html = renderToStaticMarkup(<SentenceDetails />);

		expect(html).not.toContain(`/sentences/${sentenceShort.uuid}/edit`);
		expect(html).not.toContain('Delete sentence');
		expect(html).toContain('>Kenji</strong>');
	});

	it('hides both controls on an imported sentence even for an admin', () => {
		currentUser = { id: 9, isAdmin: true };

		const html = renderToStaticMarkup(<SentenceDetails />);

		expect(html).not.toContain(`/sentences/${sentenceShort.uuid}/edit`);
		expect(html).not.toContain('Delete sentence');
	});

	it('deletes after confirming and goes back to the list', async () => {
		currentUser = { id: 5, isAdmin: false };
		useSentenceQueryMock.mockReturnValue(loaded(memberSentence));
		deleteMutate.mockImplementation((_: unknown, options: { onSuccess: () => void }) => options.onSuccess());

		const { container, flush, unmount } = await renderWithAct(<SentenceDetails />);

		await flush(() => button(container, 'Delete sentence')?.click());
		await flush(() => button(document.body, 'Yes, delete the sentence')?.click());

		expect(deleteMutate).toHaveBeenCalledTimes(1);
		expect(navigate).toHaveBeenCalledWith('/sentences');

		await unmount();
	});

	it('keeps the sentence and says why when the delete fails, without server text', async () => {
		currentUser = { id: 5, isAdmin: false };
		useSentenceQueryMock.mockReturnValue(loaded(memberSentence));
		const forbidden = new AxiosError('Request failed', 'ERR_BAD_REQUEST', undefined, undefined, {
			status: 403,
			statusText: '',
			headers: {},
			config: { headers: new AxiosHeaders() },
			data: { title: 'Access denied', detail: 'Policy SentencePolicy::delete denied' },
		});
		deleteMutate.mockImplementation((_: unknown, options: { onError: (error: unknown) => void }) =>
			options.onError(forbidden),
		);

		const { container, flush, unmount } = await renderWithAct(<SentenceDetails />);

		await flush(() => button(container, 'Delete sentence')?.click());
		await flush(() => button(document.body, 'Yes, delete the sentence')?.click());

		expect(navigate).not.toHaveBeenCalled();
		expect(container.textContent).toContain("You don't have permission to change this.");
		expect(container.textContent).not.toContain('SentencePolicy');

		await unmount();
	});

	it('renders the loading page, and a sentence that cannot be loaded in its own words', () => {
		useSentenceQueryMock.mockReturnValueOnce({ isLoading: true, isError: false });
		expect(renderToStaticMarkup(<SentenceDetails />)).toContain('data-loading-family="detail"');

		useSentenceQueryMock.mockReturnValueOnce({ isLoading: false, isError: true, error: null });
		const html = renderToStaticMarkup(<SentenceDetails />);
		expect(html).toContain('This sentence couldn&#x27;t be loaded. Please try again.');
		expect(html).toContain('href="/sentences"');
	});
});
