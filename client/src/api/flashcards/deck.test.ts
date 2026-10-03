import { describe, expect, it } from 'vitest';
import { SavedListType } from '@/shared/constants/enums';
import {
	defaultStudyConfig,
	isValidStudyCombination,
	mapStudyDeck,
	parseStudyConfig,
	readDeckFailure,
	studyConfigToSearchParams,
	studyFamilyFor,
	studyFieldsFor,
} from './deck';

describe('study fields by catalogue type', () => {
	it('mirrors the backend table, known variants included', () => {
		expect(studyFieldsFor(SavedListType.KANJIS)).toEqual(['character', 'meaning', 'onyomi', 'kunyomi']);
		expect(studyFieldsFor(SavedListType.KNOWNKANJIS)).toEqual(['character', 'meaning', 'onyomi', 'kunyomi']);
		expect(studyFieldsFor(SavedListType.WORDS)).toEqual(['character', 'meaning', 'reading']);
		expect(studyFieldsFor(SavedListType.KNOWNRADICALS)).toEqual(['character', 'meaning', 'reading']);
		expect(studyFieldsFor(SavedListType.SENTENCES)).toEqual([]);
		expect(studyFamilyFor(SavedListType.ARTICLES)).toBeNull();
	});

	it('requires a character on one side and a typeable answer for typed mode', () => {
		expect(isValidStudyCombination(SavedListType.KANJIS, 'character', 'kunyomi', 'typed')).toBe(true);
		expect(isValidStudyCombination(SavedListType.KANJIS, 'meaning', 'character', 'options')).toBe(true);
		expect(isValidStudyCombination(SavedListType.KANJIS, 'meaning', 'character', 'typed')).toBe(false);
		expect(isValidStudyCombination(SavedListType.KANJIS, 'meaning', 'onyomi', 'options')).toBe(false);
		expect(isValidStudyCombination(SavedListType.KANJIS, 'character', 'reading', 'options')).toBe(false);
		expect(isValidStudyCombination(SavedListType.WORDS, 'character', 'character', 'options')).toBe(false);
	});
});

describe('parseStudyConfig', () => {
	it('round-trips through the URL', () => {
		const config = {
			prompt: 'character',
			answer: 'kunyomi',
			mode: 'typed',
			script: 'lenient',
			count: 7,
			seed: 42,
		} as const;

		const params = studyConfigToSearchParams(config, { play: '1' });

		expect(params.get('play')).toBe('1');
		expect(parseStudyConfig(params, SavedListType.KANJIS)).toEqual(config);
	});

	it('falls back to defaults for missing or invalid values', () => {
		const parsed = parseStudyConfig(new URLSearchParams('answer=nonsense&count=0&seed=-1&mode=TYPED'));

		expect(parsed).toEqual(defaultStudyConfig());
		expect(parsed.seed).toBeUndefined();
	});

	it('falls back to the default combination when the URL asks for one the type does not allow', () => {
		const parsed = parseStudyConfig(
			new URLSearchParams('prompt=character&answer=kunyomi&count=5'),
			SavedListType.WORDS,
		);

		expect(parsed.prompt).toBe('character');
		expect(parsed.answer).toBe('meaning');
		expect(parsed.count).toBe(5);
	});

	it('caps count at the backend maximum', () => {
		expect(parseStudyConfig(new URLSearchParams('count=101')).count).toBe(20);
		expect(parseStudyConfig(new URLSearchParams('count=100')).count).toBe(100);
	});
});

describe('mapStudyDeck', () => {
	it('maps the wire shape to camel-cased cards', () => {
		const deck = mapStudyDeck({
			catalogue: { uuid: 'c-1', title: 'N5 kanji', type: 6, type_label: 'Kanji' },
			config: { prompt: 'character', answer: 'meaning', mode: 'options', script: 'strict', count: 20, seed: 9 },
			cards: [
				{
					item_id: 1,
					item_uuid: 'k-1',
					prompt: { text: '学', hint: null },
					accepted_answers: ['study', 'learning'],
					display_answer: 'study',
					options: ['study', 'one', 'two', 'three'],
					meta: { jlpt: '5', grade: '1', strokes: 8 },
				},
			],
			total_items: 3,
			eligible_items: 2,
			excluded: { empty_answer_field: 1 },
		});

		expect(deck.cards[0]).toEqual({
			itemId: 1,
			itemUuid: 'k-1',
			promptText: '学',
			promptHint: null,
			acceptedAnswers: ['study', 'learning'],
			displayAnswer: 'study',
			options: ['study', 'one', 'two', 'three'],
			jlpt: '5',
			grade: '1',
			strokes: 8,
		});
		expect(deck.config.seed).toBe(9);
		expect(deck.excludedEmptyAnswerField).toBe(1);
	});
});

describe('readDeckFailure', () => {
	it('reads the Problem Details title and detail', () => {
		expect(
			readDeckFailure({
				response: {
					status: 422,
					data: { title: 'No eligible cards', detail: 'No item has a kunyomi', status: 422 },
				},
			}),
		).toEqual({ status: 422, title: 'No eligible cards', detail: 'No item has a kunyomi' });
	});

	it('degrades to a generic title for a network failure', () => {
		expect(readDeckFailure(new Error('offline'))).toEqual({
			status: null,
			title: 'The deck could not be loaded',
			detail: null,
		});
	});
});
