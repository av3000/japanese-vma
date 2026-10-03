import type { StudyCard, StudyConfig, StudyDeck } from '@/api/flashcards/deck';

/**
 * Orval-typed fixtures for flashcard stories and tests, including the hostile cases the
 * epic calls out: a 10-gloss kanji, a one-card deck, a long word.
 */

const config = (overrides: Partial<StudyConfig> = {}): StudyConfig => ({
	prompt: 'character',
	answer: 'meaning',
	mode: 'options',
	script: 'strict',
	count: 20,
	seed: 7,
	...overrides,
});

const card = (
	overrides: Partial<StudyCard> & Pick<StudyCard, 'itemId' | 'promptText' | 'acceptedAnswers'>,
): StudyCard => ({
	itemUuid: `uuid-${overrides.itemId}`,
	promptHint: null,
	displayAnswer: overrides.acceptedAnswers[0],
	options: null,
	jlpt: '5',
	grade: '1',
	strokes: 8,
	...overrides,
});

export const kanjiCards: StudyCard[] = [
	card({
		itemId: 1,
		promptText: '学',
		acceptedAnswers: ['study', 'learning', 'science'],
		options: ['one', 'study', 'water', 'fire'],
	}),
	card({
		itemId: 2,
		promptText: '水',
		acceptedAnswers: ['water'],
		options: ['water', 'study', 'one', 'fire'],
		strokes: 4,
	}),
	card({
		itemId: 3,
		promptText: '一',
		acceptedAnswers: ['one', 'one radical (no.1)'],
		options: ['fire', 'water', 'study', 'one'],
		strokes: 1,
	}),
];

export const kanjiOptionsDeck: StudyDeck = {
	catalogue: { uuid: 'c-1', title: 'N5 kanji', type: 6, type_label: 'Kanji' },
	config: config(),
	cards: kanjiCards,
	totalItems: 3,
	eligibleItems: 3,
	excludedEmptyAnswerField: 0,
};

export const kanjiTypedKunyomiDeck: StudyDeck = {
	...kanjiOptionsDeck,
	config: config({ answer: 'kunyomi', mode: 'typed' }),
	cards: [
		card({ itemId: 1, promptText: '学', acceptedAnswers: ['まな.ぶ'] }),
		card({ itemId: 2, promptText: '水', acceptedAnswers: ['みず', 'みず-'], strokes: 4 }),
	],
	totalItems: 2,
	eligibleItems: 2,
};

/** The epic's hostile case: ten glosses on one card, long options text. */
export const longGlossDeck: StudyDeck = {
	...kanjiOptionsDeck,
	cards: [
		card({
			itemId: 10,
			promptText: '生',
			acceptedAnswers: [
				'life',
				'genuine',
				'birth',
				'to be born',
				'to grow',
				'to live',
				'raw',
				'fresh',
				'pure',
				'natural',
			],
			options: ['life', 'come after (deliberately long distractor to wrap)', 'rank next', 'Asia'],
			jlpt: '5',
			strokes: 5,
		}),
	],
	totalItems: 1,
	eligibleItems: 1,
};

/** One card still gets four options; they come from the dictionary pool. */
export const oneCardDeck: StudyDeck = {
	...kanjiOptionsDeck,
	cards: [kanjiCards[1]],
	totalItems: 1,
	eligibleItems: 1,
};

export const longWordDeck: StudyDeck = {
	catalogue: { uuid: 'c-2', title: 'Long words', type: 7, type_label: 'Words' },
	config: config({ answer: 'reading', mode: 'typed' }),
	cards: [
		card({
			itemId: 20,
			promptText: '国際連合教育科学文化機関',
			promptHint: 'noun (common) (futsuumeishi)',
			acceptedAnswers: ['こくさいれんごうきょういくかがくぶんかきかん'],
			jlpt: null,
			grade: null,
			strokes: null,
		}),
	],
	totalItems: 1,
	eligibleItems: 1,
	excludedEmptyAnswerField: 0,
};

export const radicalReadingDeck: StudyDeck = {
	catalogue: { uuid: 'c-3', title: 'Radicals 1 to 3 strokes', type: 5, type_label: 'Radicals' },
	config: config({ answer: 'reading', mode: 'typed' }),
	cards: [
		card({ itemId: 30, promptText: '丨', acceptedAnswers: ['ぼう', 'bō'], jlpt: null, grade: null, strokes: 1 }),
	],
	totalItems: 1,
	eligibleItems: 1,
	excludedEmptyAnswerField: 0,
};
