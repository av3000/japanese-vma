import { describe, expect, it } from 'vitest';
import { japaneseLang } from './japaneseLang';

describe('japaneseLang', () => {
	it.each([
		['日本語の記事', 'ja'],
		['ひらがな', 'ja'],
		['カタカナ', 'ja'],
		['「引用」から始まる', 'ja'],
		['2026年の記事', 'ja'],
		['Particles は and が', undefined],
		['My kanji list', undefined],
		['', undefined],
		[null, undefined],
	])('%j gives %j', (text, expected) => {
		expect(japaneseLang(text)).toBe(expected);
	});
});
