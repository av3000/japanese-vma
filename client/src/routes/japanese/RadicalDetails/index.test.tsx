import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { radicalTree, radicalWater } from '@/components/features/japanese/related/dictionaryDetail.fixtures';
import { SavedListType } from '@/shared/constants/enums';
import RadicalDetails from './index';

const useRadicalQueryMock = vi.fn();
const useRadicalKanjiPagesMock = vi.fn();
const authorizedWidgetProps: Array<Record<string, unknown>> = [];
const relatedProps: Array<Record<string, unknown>> = [];
let isAuthenticated = true;

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to, className }: { children: ReactNode; to: string; className?: string }) => (
			<a href={to} className={className}>
				{children}
			</a>
		),
		useParams: () => ({ radical_id: 'radical-route-uuid' }),
	};
});

vi.mock('@/api/radicals/details', () => ({
	useRadicalQuery: (...args: unknown[]) => useRadicalQueryMock(...args),
}));
vi.mock('@/api/radicals/related', () => ({
	useRadicalKanjiPages: (...args: unknown[]) => useRadicalKanjiPagesMock(...args),
}));
vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({ isAuthenticated }),
}));
vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));
vi.mock('@/components/features/catalogues/AuthorizedBookmarkWidget', () => ({
	AuthorizedBookmarkWidget: (props: Record<string, unknown>) => {
		authorizedWidgetProps.push(props);
		return <div>Bookmark</div>;
	},
}));
vi.mock('@/components/features/japanese/related', async (importOriginal) => ({
	...(await importOriginal<typeof import('@/components/features/japanese/related')>()),
	RadicalKanji: (props: Record<string, unknown>) => {
		relatedProps.push(props);
		return <section>Kanji with this radical</section>;
	},
}));

describe('RadicalDetails', () => {
	beforeEach(() => {
		authorizedWidgetProps.length = 0;
		relatedProps.length = 0;
		isAuthenticated = true;
		useRadicalQueryMock.mockReturnValue({ data: { ...radicalTree, kanjis: [] }, isLoading: false, isError: false });
		useRadicalKanjiPagesMock.mockReturnValue({ total: 674, isPending: false, isError: false });
	});

	it('shows the radical as the title with its meaning, name and number', () => {
		const html = renderToStaticMarkup(<RadicalDetails />);

		expect(useRadicalQueryMock).toHaveBeenCalledWith('radical-route-uuid');
		expect(html).toMatch(/<h1 class="[^"]*glyph[^"]*" lang="ja">木<\/h1>/);
		expect(html).toContain('lang="en">tree</p>');
		expect(html).toContain('lang="ja">き</span>');
		expect(html).toContain('> ki</span>');
		expect(html).toMatch(/>Number<\/dt><dd[^>]*>No. 75<\/dd>/);
		expect(html).toMatch(/>Strokes<\/dt><dd[^>]*>4<\/dd>/);
		expect(html).toMatch(/<a href="\/radicals"[^>]*>.*Radicals<\/a>/);
	});

	it('splits the variants line off the glyph', () => {
		useRadicalQueryMock.mockReturnValue({
			data: { ...radicalWater, kanjis: [] },
			isLoading: false,
			isError: false,
		});

		const html = renderToStaticMarkup(<RadicalDetails />);

		expect(html).toContain('lang="ja">水</h1>');
		expect(html).toContain('>Variants</dt>');
		expect(html).toMatch(/<li[^>]*>氵<\/li><li[^>]*>氺<\/li>/);
	});

	it('counts the kanji from the list query, and says so while it loads', () => {
		expect(renderToStaticMarkup(<RadicalDetails />)).toMatch(/>Kanji using it<\/dt><dd[^>]*>674<\/dd>/);
		expect(useRadicalKanjiPagesMock).toHaveBeenCalledWith(radicalTree.uuid);

		useRadicalKanjiPagesMock.mockReturnValue({ total: 0, isPending: true, isError: false });
		expect(renderToStaticMarkup(<RadicalDetails />)).toMatch(/>Kanji using it<\/dt><dd[^>]*>Counting…<\/dd>/);

		useRadicalKanjiPagesMock.mockReturnValue({ total: 0, isPending: false, isError: true });
		expect(renderToStaticMarkup(<RadicalDetails />)).not.toContain('Kanji using it');
	});

	it('offers a labelled Save by the response id, and passes the radical to its kanji list', () => {
		renderToStaticMarkup(<RadicalDetails />);

		expect(authorizedWidgetProps[0]).toMatchObject({
			entityId: 75,
			instanceObjectType: SavedListType.RADICALS,
			isKnownType: SavedListType.KNOWNRADICALS,
			label: 'Save to a catalogue',
		});
		expect(relatedProps[0]).toEqual({ radicalUuid: radicalTree.uuid, showSave: true });

		isAuthenticated = false;
		authorizedWidgetProps.length = 0;
		renderToStaticMarkup(<RadicalDetails />);
		expect(authorizedWidgetProps).toHaveLength(0);
	});

	it('explains a radical that cannot be loaded, with a way back', () => {
		useRadicalQueryMock.mockReturnValue({ data: undefined, isLoading: false, isError: true, error: null });

		const html = renderToStaticMarkup(<RadicalDetails />);

		expect(html).toContain('This radical couldn&#x27;t be loaded. Please try again.');
		expect(html).toContain('href="/radicals"');
	});
});
