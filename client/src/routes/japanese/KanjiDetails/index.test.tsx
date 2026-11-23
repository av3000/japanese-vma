import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { AxiosError, AxiosHeaders } from 'axios';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
	kanjiManyMeanings,
	kanjiNoMeaning,
	kanjiNoReadings,
	kanjiPerson,
} from '@/components/features/japanese/related/dictionaryDetail.fixtures';
import { SavedListType } from '@/shared/constants/enums';
import KanjiDetails from './index';

const useKanjiQueryMock = vi.fn();
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
		useParams: () => ({ kanji_id: 'kanji-route-id' }),
	};
});

vi.mock('@/api/kanjis/details', () => ({
	useKanjiQuery: (...args: unknown[]) => useKanjiQueryMock(...args),
}));

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({ isAuthenticated }),
}));

vi.mock('@/components/features/catalogues/AuthorizedBookmarkWidget', () => ({
	AuthorizedBookmarkWidget: (props: Record<string, unknown>) => {
		authorizedWidgetProps.push(props);
		return <div>Authorized bookmark widget</div>;
	},
}));

vi.mock('@/components/features/japanese/related', async (importOriginal) => ({
	...(await importOriginal<typeof import('@/components/features/japanese/related')>()),
	KanjiRelated: (props: Record<string, unknown>) => {
		relatedProps.push(props);
		return <section>Related to this kanji</section>;
	},
}));

vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));

const loaded = (data: unknown) => ({ data, isLoading: false, isError: false, error: null });

const httpError = (status: number) =>
	new AxiosError('Request failed', 'ERR_BAD_REQUEST', undefined, undefined, {
		status,
		statusText: '',
		headers: {},
		config: { headers: new AxiosHeaders() },
		data: { title: 'Kanji not found', detail: 'Kanji 4d98c557 not found' },
	});

describe('KanjiDetails', () => {
	beforeEach(() => {
		authorizedWidgetProps.length = 0;
		relatedProps.length = 0;
		isAuthenticated = true;
		useKanjiQueryMock.mockReturnValue(
			loaded({ ...kanjiPerson, viewer_catalogue_state: { is_saved: true, is_known: false } }),
		);
	});

	it('shows the kanji as the page title in the Japanese serif face, with its meaning and the way back', () => {
		const html = renderToStaticMarkup(<KanjiDetails />);

		expect(useKanjiQueryMock).toHaveBeenCalledWith('kanji-route-id');
		expect(html).toMatch(/<h1 class="[^"]*glyph[^"]*" lang="ja">人<\/h1>/);
		expect(html.match(/<h1/g)).toHaveLength(1);
		expect(html).toContain('lang="en">person</p>');
		expect(html).toMatch(/<a href="\/kanjis"[^>]*>.*Kanji<\/a>/);
		expect(html).toContain('aria-label="About this kanji"');
	});

	it('lists the readings, with suffix marks kept and empty kinds left out', () => {
		const html = renderToStaticMarkup(<KanjiDetails />);

		expect(html).toContain('>On’yomi</dt>');
		expect(html).toContain('<li>ジン</li><li>ニン</li>');
		expect(html).toContain('>Kun’yomi</dt>');
		expect(html).toContain('<li>-り</li>');
		expect(html).toContain('>Name readings</dt>');

		useKanjiQueryMock.mockReturnValue(loaded(kanjiNoMeaning));
		const sparse = renderToStaticMarkup(<KanjiDetails />);
		expect(sparse).toContain('>On’yomi</dt>');
		expect(sparse).not.toContain('Kun’yomi');
		expect(sparse).not.toContain('Name readings');
	});

	it('sets the okurigana apart after a dot', () => {
		useKanjiQueryMock.mockReturnValue(loaded(kanjiManyMeanings));

		const html = renderToStaticMarkup(<KanjiDetails />);

		expect(html).toMatch(
			/<li>な<span class="[^"]*okurigana[^"]*"><span aria-hidden="true">·<\/span>る<\/span><\/li>/,
		);
	});

	it('says so when a kanji has no readings or no meaning, never a dash', () => {
		useKanjiQueryMock.mockReturnValue(loaded(kanjiNoReadings));
		expect(renderToStaticMarkup(<KanjiDetails />)).toContain('No readings are recorded for this kanji.');

		useKanjiQueryMock.mockReturnValue(loaded(kanjiNoMeaning));
		const html = renderToStaticMarkup(<KanjiDetails />);
		expect(html).toContain('No English meaning recorded.');
		expect(html).not.toMatch(/<(dd|li|p)[^>]*>-<\//);
	});

	it('shows the facts the dictionary has in the rail, and leaves out the rest', () => {
		const html = renderToStaticMarkup(<KanjiDetails />);

		expect(html).toMatch(/>Strokes<\/dt><dd[^>]*>2<\/dd>/);
		expect(html).toMatch(/>JLPT<\/dt><dd[^>]*>.*N5.*<\/dd>/);
		expect(html).toMatch(/>Grade<\/dt><dd[^>]*>Grade 1<\/dd>/);
		expect(html).toMatch(/>Frequency<\/dt><dd[^>]*>#5<\/dd>/);
		expect(html).toMatch(/>Radical<\/dt><dd[^>]*><a href="\/radical\/9"[^>]*><span lang="ja">人<\/span>/);

		useKanjiQueryMock.mockReturnValue(loaded(kanjiNoMeaning));
		const sparse = renderToStaticMarkup(<KanjiDetails />);
		expect(sparse).not.toContain('>JLPT</dt>');
		expect(sparse).not.toContain('>Grade</dt>');
		expect(sparse).not.toContain('>Frequency</dt>');
		expect(sparse).toContain('href="/radical/37"');
	});

	it('offers a labelled Save with the viewer’s state, by the numeric kanji id', () => {
		renderToStaticMarkup(<KanjiDetails />);

		expect(authorizedWidgetProps).toHaveLength(1);
		expect(authorizedWidgetProps[0]).toMatchObject({
			entityId: kanjiPerson.id,
			instanceObjectType: SavedListType.KANJIS,
			isKnownType: SavedListType.KNOWNKANJIS,
			modalTitle: 'Choose Kanji List to add',
			initialIsBookmarked: true,
			initialIsKnown: false,
			loadOnMount: false,
			label: 'Save to a catalogue',
		});
		expect(relatedProps[0]).toMatchObject({
			kanji: expect.objectContaining({ uuid: kanjiPerson.uuid }),
			showSave: true,
		});
	});

	it('keeps the entry and its related lists public, without Save, for guests', () => {
		isAuthenticated = false;

		const html = renderToStaticMarkup(<KanjiDetails />);

		expect(html).toContain('lang="ja">人</h1>');
		expect(html).toContain('Related to this kanji');
		expect(authorizedWidgetProps).toHaveLength(0);
		expect(relatedProps[0]).toMatchObject({ showSave: false });
	});

	it('explains a missing kanji in its own words, with a way back', () => {
		useKanjiQueryMock.mockReturnValue({ data: undefined, isLoading: false, isError: true, error: httpError(404) });

		const html = renderToStaticMarkup(<KanjiDetails />);

		expect(html).toContain('This kanji doesn&#x27;t exist. It may have been deleted.');
		expect(html).toContain('href="/kanjis"');
		expect(html).not.toContain('Kanji 4d98c557 not found');
	});

	it('shows the loading page while the kanji loads', () => {
		useKanjiQueryMock.mockReturnValue({ data: undefined, isLoading: true, isError: false, error: null });

		expect(renderToStaticMarkup(<KanjiDetails />)).toContain('data-loading-family="detail"');
	});
});
