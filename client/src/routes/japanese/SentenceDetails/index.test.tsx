import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { SavedListType } from '@/shared/constants/enums';
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
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
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
	useAuth: () => ({ isAuthenticated: true, user: currentUser }),
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

const sentenceData = {
	id: 77,
	uuid: 'sentence-uuid',
	user_id: null as number | null,
	tatoeba_entry: '7777',
	content: '火を見ます。',
	kanjis: [{ uuid: 'kanji-uuid', character: '火', meanings: ['fire'] }],
};

describe('SentenceDetails', () => {
	beforeEach(() => {
		authorizedWidgetProps.length = 0;
		commentsBlockProps.length = 0;
		deleteMutate.mockReset();
		navigate.mockClear();
		currentUser = null;
		useSentenceQueryMock.mockReturnValue({ data: sentenceData, isLoading: false, isError: false });
	});

	it('uses the UUID route and response id without word behavior', () => {
		const html = renderToStaticMarkup(<SentenceDetails />);

		expect(useSentenceQueryMock).toHaveBeenCalledWith('sentence-route-uuid');
		expect(authorizedWidgetProps[0]).toMatchObject({
			entityId: 77,
			instanceObjectType: SavedListType.SENTENCES,
			isKnownType: SavedListType.KNOWNSENTENCES,
		});
		expect(html).toContain('/kanji/kanji-uuid');
		expect(html).toContain('7777');
	});

	// The route segment is a uuid and the entity's numeric id is a different value,
	// so a seam that confused the two would still render - these two assertions are
	// what tell them apart.
	it('composes comments through the shared seam on the sentence parent', () => {
		renderToStaticMarkup(<SentenceDetails />);

		expect(commentsBlockProps).toHaveLength(1);
		expect(commentsBlockProps[0]).toEqual({
			parent: 'sentence',
			entityId: 77,
			entityUuid: 'sentence-uuid',
		});
	});

	it('takes the comment entity id from the loaded response, never from the route', () => {
		renderToStaticMarkup(<SentenceDetails />);

		expect(commentsBlockProps[0]).toMatchObject({ entityId: sentenceData.id });
		expect(commentsBlockProps[0]).not.toMatchObject({ entityUuid: 'sentence-route-uuid' });
	});

	it('renders loading and failure states', () => {
		// Was asserting 'Loading...' and failing on develop before this change:
		// the route renders <PageLoading family="detail" />, whose label is 'Loading page.'.
		useSentenceQueryMock.mockReturnValueOnce({ isLoading: true, isError: false });
		expect(renderToStaticMarkup(<SentenceDetails />)).toContain('data-loading-family="detail"');

		useSentenceQueryMock.mockReturnValueOnce({ isLoading: false, isError: true });
		expect(renderToStaticMarkup(<SentenceDetails />)).toContain('Sentence could not be loaded.');
	});

	it('offers edit and delete to the author', () => {
		currentUser = { id: 5, isAdmin: false };
		useSentenceQueryMock.mockReturnValue({
			data: { ...sentenceData, user_id: 5 },
			isLoading: false,
			isError: false,
		});

		const html = renderToStaticMarkup(<SentenceDetails />);

		expect(html).toContain('/sentences/sentence-uuid/edit');
		expect(html).toContain('Delete sentence');
	});

	it('hides both controls from a viewer who does not own the sentence', () => {
		currentUser = { id: 42, isAdmin: false };
		useSentenceQueryMock.mockReturnValue({
			data: { ...sentenceData, user_id: 5 },
			isLoading: false,
			isError: false,
		});

		const html = renderToStaticMarkup(<SentenceDetails />);

		expect(html).not.toContain('/sentences/sentence-uuid/edit');
		expect(html).not.toContain('Delete sentence');
	});

	it('hides both controls on an imported sentence even for an admin', () => {
		currentUser = { id: 9, isAdmin: true };

		const html = renderToStaticMarkup(<SentenceDetails />);

		expect(html).not.toContain('/sentences/sentence-uuid/edit');
		expect(html).not.toContain('Delete sentence');
	});
});
