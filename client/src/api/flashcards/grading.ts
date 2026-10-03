import type { FlashcardField } from '@/api/generated/model/flashcardField';
import type { ScriptStrictness } from '@/api/generated/model/scriptStrictness';
import { toHiragana, toKatakana } from './kana';

/**
 * Decides whether a typed flashcard answer is correct. This is the only grader in the
 * product (epic #413: grading happens in the client); the backend stores the verdict.
 *
 * Both sides are normalized with NFKC, trimmed, and have internal whitespace collapsed.
 * Then, per answer field:
 *
 * | Field            | Expected side                                                       | Given side                           |
 * |------------------|---------------------------------------------------------------------|--------------------------------------|
 * | meaning          | lowercase; drop "(…)"; drop leading "to "; drop trailing punctuation | same                                 |
 * | kunyomi          | remove the okurigana "."; remove a leading or trailing "-"           | lenient: katakana folded to hiragana |
 * | onyomi           | as stored (katakana)                                                 | lenient: hiragana folded to katakana |
 * | reading          | folded to hiragana; romaji lowercased with macrons expanded          | folded to hiragana; lowercased       |
 * | character        | as stored                                                            | as stored                            |
 *
 * `reading` covers word furigana (`どうじょう`) and radical readings, which arrive as two
 * accepted answers, kana and romaji (`ぼう`, `bō`). A macron expands to its short and long
 * spellings, so `bō` accepts `bo`, `bou` and `boo`.
 *
 * An empty answer is never correct; the UI does not submit one.
 */

export type ScriptMode = ScriptStrictness;

export interface GradeInput {
	answerField: FlashcardField;
	script: ScriptMode;
	/** Raw accepted answers as the deck delivers them. */
	acceptedAnswers: readonly string[];
	/** What the learner typed. */
	given: string;
}

export interface GradeResult {
	correct: boolean;
	/** The raw accepted answer that matched, for feedback. */
	matched: string | null;
}

const MACRON_EXPANSIONS: Record<string, readonly string[]> = {
	ā: ['a', 'aa'],
	ī: ['i', 'ii'],
	ū: ['u', 'uu'],
	ē: ['e', 'ee', 'ei'],
	ō: ['o', 'ou', 'oo'],
};

const base = (value: string): string => value.normalize('NFKC').trim().replace(/\s+/g, ' ');

const normalizeMeaning = (value: string): string =>
	base(value)
		.toLowerCase()
		.replace(/\([^)]*\)/g, '')
		.trim()
		.replace(/^to\s+/, '')
		.replace(/[.,;:!?\s]+$/, '')
		.replace(/\s+/g, ' ')
		.trim();

const normalizeKunyomiExpected = (value: string): string =>
	base(value)
		.replace(/\./g, '')
		.replace(/^-+|-+$/g, '');

const normalizeReading = (value: string): string => toHiragana(base(value)).toLowerCase();

/** Every spelling an expected romaji reading with macrons may be typed as. */
const expandMacrons = (value: string): string[] => {
	let variants = [''];

	for (const char of value) {
		const expansions = MACRON_EXPANSIONS[char] ?? [char];
		variants = variants.flatMap((prefix) => expansions.map((expansion) => prefix + expansion));
	}

	return variants;
};

/** All normalized forms one raw accepted answer may be matched against. */
export const expectedForms = (answer: string, field: FlashcardField): string[] => {
	switch (field) {
		case 'meaning':
			return [normalizeMeaning(answer)];
		case 'kunyomi':
			return [normalizeKunyomiExpected(answer)];
		case 'onyomi':
			return [base(answer)];
		case 'reading':
			return expandMacrons(normalizeReading(answer));
		case 'character':
			return [base(answer)];
	}
};

/** The normalized form of what the learner typed. */
export const givenForm = (given: string, field: FlashcardField, script: ScriptMode): string => {
	switch (field) {
		case 'meaning':
			return normalizeMeaning(given);
		case 'kunyomi':
			return script === 'lenient' ? toHiragana(base(given)) : base(given);
		case 'onyomi':
			return script === 'lenient' ? toKatakana(base(given)) : base(given);
		case 'reading':
			return normalizeReading(given);
		case 'character':
			return base(given);
	}
};

export const grade = ({ answerField, script, acceptedAnswers, given }: GradeInput): GradeResult => {
	const typed = givenForm(given, answerField, script);

	if (typed === '') {
		return { correct: false, matched: null };
	}

	for (const answer of acceptedAnswers) {
		if (expectedForms(answer, answerField).some((form) => form !== '' && form === typed)) {
			return { correct: true, matched: answer };
		}
	}

	return { correct: false, matched: null };
};
