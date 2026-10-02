import { describe, expect, it } from 'vitest';
import { normalizeRefine, scopedSearchUrl } from './searchScopes';

describe('scopedSearchUrl', () => {
	it.each([
		['articles', '/articles?q=%E6%B0%B4'],
		['kanji', '/kanjis?keyword=%E6%B0%B4'],
		['words', '/words?keyword=%E6%B0%B4'],
		['sentences', '/sentences?keyword=%E6%B0%B4'],
		['radicals', '/radicals?keyword=%E6%B0%B4'],
	] as const)('sends %s to its list with the trimmed keyword', (scope, url) => {
		expect(scopedSearchUrl(scope, '  水 ')).toBe(url);
	});

	it.each(['articles', 'kanji', 'words', 'sentences', 'radicals'] as const)(
		'opens the %s list unfiltered for an empty or whitespace-only keyword',
		(scope) => {
			expect(scopedSearchUrl(scope, '')).not.toContain('?');
			expect(scopedSearchUrl(scope, '   ')).not.toContain('?');
		},
	);

	it('passes a short Articles keyword through; the list enforces its own minimum', () => {
		expect(scopedSearchUrl('articles', 'a')).toBe('/articles?q=a');
	});

	it.each([
		['kanji with a level', 'kanji', '水', ['5'], '/kanjis?keyword=%E6%B0%B4&jlpt=5'],
		['kanji, uncommon only', 'kanji', '', ['-'], '/kanjis?jlpt=-'],
		['kanji keeps one level', 'kanji', '水', ['5', '4'], '/kanjis?keyword=%E6%B0%B4&jlpt=5'],
		['kanji drops an unknown level', 'kanji', '水', ['n5'], '/kanjis?keyword=%E6%B0%B4'],
		[
			'articles with levels, in list order',
			'articles',
			'news',
			['n3', 'n5'],
			'/articles?q=news&jlpt_levels%5B%5D=n5&jlpt_levels%5B%5D=n3',
		],
		['articles, levels only', 'articles', '', ['n1'], '/articles?jlpt_levels%5B%5D=n1'],
		['articles drop a kanji level', 'articles', 'news', ['5'], '/articles?q=news'],
		['words ignore jlpt until #399', 'words', 'たべる', ['5'], '/words?keyword=%E3%81%9F%E3%81%B9%E3%82%8B'],
		['sentences take keyword only', 'sentences', 'water', ['5'], '/sentences?keyword=water'],
		['radicals take keyword only', 'radicals', 'water', ['n5'], '/radicals?keyword=water'],
	] as const)('refine: %s', (_, scope, keyword, refine, url) => {
		expect(scopedSearchUrl(scope, keyword, refine)).toBe(url);
	});
});

describe('normalizeRefine', () => {
	it.each([
		['kanji', ['5'], ['5']],
		['kanji', ['-', '1'], ['1']],
		['kanji', ['x'], []],
		['articles', ['n1', 'n5', 'n1'], ['n5', 'n1']],
		['articles', ['uncommon'], []],
		['words', ['5'], []],
		['sentences', ['5'], []],
	] as const)('%s %j -> %j', (scope, values, expected) => {
		expect(normalizeRefine(scope, values)).toEqual(expected);
	});
});
