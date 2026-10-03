import { describe, expect, it } from 'vitest';
import { expectedForms, grade } from './grading';

const asia = ['Asia', 'rank next', 'come after', '-ous'];

describe('grade: meaning', () => {
	it('accepts any one gloss, ignoring case, spacing and trailing punctuation', () => {
		expect(grade({ answerField: 'meaning', script: 'strict', acceptedAnswers: asia, given: 'rank next' })).toEqual({
			correct: true,
			matched: 'rank next',
		});
		expect(
			grade({ answerField: 'meaning', script: 'strict', acceptedAnswers: asia, given: 'Rank  Next.' }).correct,
		).toBe(true);
		expect(
			grade({ answerField: 'meaning', script: 'strict', acceptedAnswers: asia, given: 'Europe' }).correct,
		).toBe(false);
	});

	it('drops a leading "to" and parenthesised qualifiers on both sides', () => {
		expect(
			grade({ answerField: 'meaning', script: 'strict', acceptedAnswers: ['to learn', 'study'], given: 'learn' })
				.correct,
		).toBe(true);
		expect(
			grade({
				answerField: 'meaning',
				script: 'strict',
				acceptedAnswers: ['ditto mark (symbol)'],
				given: 'ditto mark',
			}).correct,
		).toBe(true);
		expect(
			grade({ answerField: 'meaning', script: 'strict', acceptedAnswers: ['learn'], given: 'to learn (a skill)' })
				.correct,
		).toBe(true);
	});

	it('folds full-width latin through NFKC', () => {
		expect(
			grade({ answerField: 'meaning', script: 'strict', acceptedAnswers: ['study'], given: 'ｓｔｕｄｙ' })
				.correct,
		).toBe(true);
	});
});

describe('grade: kunyomi', () => {
	it('ignores the okurigana dot and edge hyphens on the expected side', () => {
		expect(grade({ answerField: 'kunyomi', script: 'strict', acceptedAnswers: ['つ.ぐ'], given: 'つぐ' })).toEqual({
			correct: true,
			matched: 'つ.ぐ',
		});
		expect(
			grade({ answerField: 'kunyomi', script: 'strict', acceptedAnswers: ['うつく.しい'], given: 'うつくしい' })
				.correct,
		).toBe(true);
		expect(
			grade({ answerField: 'kunyomi', script: 'strict', acceptedAnswers: ['-がた'], given: 'がた' }).correct,
		).toBe(true);
	});

	it('requires hiragana when strict, accepts katakana when lenient', () => {
		expect(
			grade({ answerField: 'kunyomi', script: 'strict', acceptedAnswers: ['つ.ぐ'], given: 'ツグ' }).correct,
		).toBe(false);
		expect(
			grade({ answerField: 'kunyomi', script: 'lenient', acceptedAnswers: ['つ.ぐ'], given: 'ツグ' }).correct,
		).toBe(true);
	});
});

describe('grade: onyomi', () => {
	it('requires katakana when strict, accepts hiragana when lenient', () => {
		expect(
			grade({ answerField: 'onyomi', script: 'strict', acceptedAnswers: ['ア', 'アク'], given: 'あく' }).correct,
		).toBe(false);
		expect(
			grade({ answerField: 'onyomi', script: 'lenient', acceptedAnswers: ['ア', 'アク'], given: 'あく' }),
		).toEqual({
			correct: true,
			matched: 'アク',
		});
		expect(
			grade({ answerField: 'onyomi', script: 'strict', acceptedAnswers: ['ア', 'アク'], given: 'アク ' }).correct,
		).toBe(true);
	});
});

describe('grade: reading', () => {
	it('accepts either kana script for a word reading regardless of strictness', () => {
		expect(
			grade({ answerField: 'reading', script: 'strict', acceptedAnswers: ['どうじょう'], given: 'ドウジョウ' })
				.correct,
		).toBe(true);
	});

	it('accepts kana or romaji for a radical reading, with macrons expanded', () => {
		const radical = ['ぼう', 'bō'];

		expect(
			grade({ answerField: 'reading', script: 'strict', acceptedAnswers: radical, given: 'ぼう' }).matched,
		).toBe('ぼう');
		expect(
			grade({ answerField: 'reading', script: 'strict', acceptedAnswers: radical, given: 'bou' }).matched,
		).toBe('bō');
		expect(grade({ answerField: 'reading', script: 'strict', acceptedAnswers: radical, given: 'bo' }).correct).toBe(
			true,
		);
		expect(
			grade({ answerField: 'reading', script: 'strict', acceptedAnswers: radical, given: 'Boo' }).correct,
		).toBe(true);
		expect(grade({ answerField: 'reading', script: 'strict', acceptedAnswers: radical, given: 'ba' }).correct).toBe(
			false,
		);
	});

	it('expands every macron in a multi-syllable romaji reading', () => {
		expect(expectedForms('chōkō', 'reading')).toEqual(
			expect.arrayContaining(['choko', 'chouko', 'chokou', 'choukou', 'chookoo']),
		);
	});
});

describe('grade: empty and character', () => {
	it('never marks an empty or whitespace-only answer correct', () => {
		expect(grade({ answerField: 'meaning', script: 'strict', acceptedAnswers: ['one'], given: '' }).correct).toBe(
			false,
		);
		expect(
			grade({ answerField: 'kunyomi', script: 'strict', acceptedAnswers: ['ひと'], given: '   ' }).correct,
		).toBe(false);
	});

	it('never matches an accepted answer that normalizes to nothing', () => {
		expect(
			grade({ answerField: 'meaning', script: 'strict', acceptedAnswers: ['(obsolete)'], given: '' }).correct,
		).toBe(false);
	});

	it('compares characters as stored', () => {
		expect(
			grade({ answerField: 'character', script: 'strict', acceptedAnswers: ['学'], given: ' 学 ' }).correct,
		).toBe(true);
		expect(
			grade({ answerField: 'character', script: 'strict', acceptedAnswers: ['学'], given: '字' }).correct,
		).toBe(false);
	});
});
