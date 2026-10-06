import { describe, expect, it } from 'vitest';
import { isKana, toHiragana, toKatakana } from './kana';

describe('kana folding', () => {
	it('folds katakana to hiragana and back, syllable for syllable', () => {
		expect(toHiragana('ドウジョウ')).toBe('どうじょう');
		expect(toKatakana('どうじょう')).toBe('ドウジョウ');
		expect(toHiragana('ッ')).toBe('っ');
	});

	it('leaves the prolonged sound mark, latin, kanji and punctuation alone', () => {
		expect(toHiragana('コーヒー')).toBe('こーひー');
		expect(toKatakana('学ぶ (manabu)')).toBe('学ブ (manabu)');
	});

	it('is a no-op on text already in the target script', () => {
		expect(toHiragana('あいう')).toBe('あいう');
		expect(toKatakana('アイウ')).toBe('アイウ');
	});

	it('recognises pure kana strings', () => {
		expect(isKana('がっこう')).toBe(true);
		expect(isKana('コーヒー')).toBe(true);
		expect(isKana('学校')).toBe(false);
		expect(isKana('')).toBe(false);
	});
});
