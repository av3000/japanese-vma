import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { SavedListType } from '@/shared/constants/enums';
import SentenceDetails from './index';

const useSentenceQueryMock = vi.fn();
const authorizedWidgetProps: Array<Record<string, unknown>> = [];

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useParams: () => ({ sentence_id: 'sentence-route-uuid' }),
	};
});

vi.mock('@/api/sentences/details', () => ({
	useSentenceQuery: (...args: unknown[]) => useSentenceQueryMock(...args),
}));
vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ isAuthenticated: true }) }));
vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));
vi.mock('@/components/features/catalogues/AuthorizedBookmarkWidget', () => ({
	AuthorizedBookmarkWidget: (props: Record<string, unknown>) => {
		authorizedWidgetProps.push(props);
		return <div>Bookmark</div>;
	},
}));

describe('SentenceDetails', () => {
	beforeEach(() => {
		authorizedWidgetProps.length = 0;
		useSentenceQueryMock.mockReturnValue({
			data: {
				id: 77,
				uuid: 'sentence-uuid',
				user_id: null,
				tatoeba_entry: '7777',
				content: '火を見ます。',
				kanjis: [{ uuid: 'kanji-uuid', character: '火', meanings: ['fire'] }],
			},
			isLoading: false,
			isError: false,
		});
	});

	it('uses the UUID route and response id without comments or word behavior', () => {
		const html = renderToStaticMarkup(<SentenceDetails />);

		expect(useSentenceQueryMock).toHaveBeenCalledWith('sentence-route-uuid');
		expect(authorizedWidgetProps[0]).toMatchObject({
			entityId: 77,
			instanceObjectType: SavedListType.SENTENCES,
			isKnownType: SavedListType.KNOWNSENTENCES,
		});
		expect(html).toContain('/kanji/kanji-uuid');
		expect(html).toContain('7777');
		expect(html).not.toContain('Comments');
	});

	it('renders loading and failure states', () => {
		useSentenceQueryMock.mockReturnValueOnce({ isLoading: true, isError: false });
		expect(renderToStaticMarkup(<SentenceDetails />)).toContain('Loading...');

		useSentenceQueryMock.mockReturnValueOnce({ isLoading: false, isError: true });
		expect(renderToStaticMarkup(<SentenceDetails />)).toContain('Sentence could not be loaded.');
	});
});
