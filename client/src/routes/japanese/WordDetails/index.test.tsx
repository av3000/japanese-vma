import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { AxiosError, AxiosHeaders } from 'axios';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { mapWordDetail } from '@/api/words/details';
import {
	wordKanaOnly,
	wordManyKanji,
	wordManyMeanings,
} from '@/components/features/japanese/related/dictionaryDetail.fixtures';
import { SavedListType } from '@/shared/constants/enums';
import WordDetails from './index';

const useWordQueryMock = vi.fn();
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
		useParams: () => ({ word_id: 'word-route-uuid' }),
	};
});

vi.mock('@/api/words/details', async (importOriginal) => ({
	...(await importOriginal<typeof import('@/api/words/details')>()),
	useWordQuery: (...args: unknown[]) => useWordQueryMock(...args),
}));

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({ isAuthenticated }),
}));

vi.mock('@/components/features/catalogues/AuthorizedBookmarkWidget', () => ({
	AuthorizedBookmarkWidget: (props: Record<string, unknown>) => {
		authorizedWidgetProps.push(props);
		return <div>Bookmark</div>;
	},
}));

vi.mock('@/components/features/japanese/related', async (importOriginal) => ({
	...(await importOriginal<typeof import('@/components/features/japanese/related')>()),
	WordRelated: (props: Record<string, unknown>) => {
		relatedProps.push(props);
		return <section>Related to this word</section>;
	},
}));

vi.mock('@/assets/images/spinner.gif', () => ({ default: 'spinner.gif' }));

const loaded = (data: Parameters<typeof mapWordDetail>[0]) => ({
	data: mapWordDetail(data),
	isLoading: false,
	isError: false,
	error: null,
});

describe('WordDetails', () => {
	beforeEach(() => {
		authorizedWidgetProps.length = 0;
		relatedProps.length = 0;
		isAuthenticated = true;
		useWordQueryMock.mockReturnValue(loaded(wordManyMeanings));
	});

	it('shows the word as the title with its reading and first meanings', () => {
		const html = renderToStaticMarkup(<WordDetails />);

		expect(useWordQueryMock).toHaveBeenCalledWith('word-route-uuid');
		expect(html).toMatch(/<h1 class="[^"]*word[^"]*" lang="ja">出る<\/h1>/);
		expect(html).toContain('lang="ja">でる</p>');
		expect(html).toContain('lang="en">to leave, to exit, to go out</p>');
		expect(html).toMatch(/<a href="\/words"[^>]*>.*Words<\/a>/);
	});

	it('lists the meanings as numbered senses, in order', () => {
		const html = renderToStaticMarkup(<WordDetails />);
		const senses = html.match(/<li class="[^"]*sense[^"]*">/g) ?? [];

		expect(html).toContain('>Meanings</h2>');
		expect(html).toContain('<ol class=');
		expect(senses).toHaveLength(wordManyMeanings.senses.length);
		expect(html).toContain('>to leave; to exit; to go out; to come out; to get out</p>');
		expect(html.indexOf('to get out')).toBeLessThan(html.indexOf('to graduate'));
	});

	it('hides the reading of a word written in kana, and shows no JLPT it does not have', () => {
		useWordQueryMock.mockReturnValue(loaded(wordKanaOnly));

		const html = renderToStaticMarkup(<WordDetails />);

		expect(html).toContain('lang="ja">どうぞ</h1>');
		expect(html).not.toMatch(/<p[^>]*lang="ja">-<\/p>/);
		expect(html).not.toContain('>JLPT</dt>');
		expect(html).toMatch(/>Kanji<\/dt><dd[^>]*>0<\/dd>/);
	});

	it('shows part of speech and the kanji count in the rail', () => {
		useWordQueryMock.mockReturnValue(loaded(wordManyKanji));

		const html = renderToStaticMarkup(<WordDetails />);

		expect(html).toContain('>Part of speech</dt>');
		expect(html).toMatch(/>Kanji<\/dt><dd[^>]*>18<\/dd>/);
		expect(relatedProps[0]).toMatchObject({ word: expect.objectContaining({ id: wordManyKanji.id }) });
	});

	it('offers a labelled Save by the numeric word id to signed-in viewers only', () => {
		renderToStaticMarkup(<WordDetails />);

		expect(authorizedWidgetProps[0]).toMatchObject({
			entityId: wordManyMeanings.id,
			instanceObjectType: SavedListType.WORDS,
			isKnownType: SavedListType.KNOWNWORDS,
			label: 'Save to a catalogue',
		});

		isAuthenticated = false;
		authorizedWidgetProps.length = 0;
		renderToStaticMarkup(<WordDetails />);
		expect(authorizedWidgetProps).toHaveLength(0);
	});

	it('renders the loading page, and a missing word in its own words', () => {
		useWordQueryMock.mockReturnValueOnce({ isLoading: true, isError: false });
		expect(renderToStaticMarkup(<WordDetails />)).toContain('data-loading-family="detail"');

		const notFound = new AxiosError('Request failed', 'ERR_BAD_REQUEST', undefined, undefined, {
			status: 404,
			statusText: '',
			headers: {},
			config: { headers: new AxiosHeaders() },
			data: {},
		});
		useWordQueryMock.mockReturnValueOnce({ isLoading: false, isError: true, error: notFound });
		const html = renderToStaticMarkup(<WordDetails />);
		expect(html).toContain('This word doesn&#x27;t exist. It may have been deleted.');
		expect(html).toContain('href="/words"');
	});
});
