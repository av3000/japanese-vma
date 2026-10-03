import type { KanjiResource, RadicalResource, SentenceResource, WordResource } from '@/api/generated/model';

/*
 * Dictionary list rows shaped like the real `/kanjis`, `/words`, `/sentences` and `/radicals`
 * payloads, including how the API reports missing values: `["-"]` for readings and meanings,
 * `frequency: 0`, `furigana: "-"`, `jlpt: "-"` or `null`. The hostile rows come from the UI-DICT
 * data check (#427).
 */

let nextId = 1;
const uuid = (id: number) => `00000000-0000-4000-8000-${String(id).padStart(12, '0')}`;

const kanji = (fields: Partial<KanjiResource> & Pick<KanjiResource, 'character'>) => {
	const id = nextId++;

	return {
		id,
		uuid: uuid(id),
		onyomi: ['-'],
		kunyomi: ['-'],
		meanings: ['-'],
		nanori: ['-'],
		grade: null,
		stroke_count: 1,
		jlpt: null,
		frequency: 0,
		radicals: [],
		radical_parts: [],
		viewer_catalogue_state: null,
		...fields,
	} satisfies KanjiResource;
};

const word = (fields: Partial<WordResource> & Pick<WordResource, 'word' | 'meaning'>) => {
	const id = nextId++;

	return {
		id,
		uuid: uuid(id),
		furigana: '-',
		jlpt: '-',
		meanings: fields.meaning.split(', '),
		word_types: [],
		writing_elements: [],
		reading_elements: [],
		word_type: '',
		word_k_ele: '[]',
		furigana_r_ele: '[]',
		sense: null,
		viewer_catalogue_state: null,
		...fields,
	} satisfies WordResource;
};

const sentence = (fields: Partial<SentenceResource> & Pick<SentenceResource, 'content'>) => {
	const id = nextId++;

	return { id, uuid: uuid(id), user_id: null, tatoeba_entry: null, ...fields } satisfies SentenceResource;
};

const radical = (fields: Partial<RadicalResource>) => {
	const id = nextId++;

	return {
		id,
		uuid: uuid(id),
		radical: null,
		strokes: null,
		meaning: null,
		hiragana: null,
		...fields,
	} satisfies RadicalResource;
};

export const kanjiRows: KanjiResource[] = [
	kanji({
		character: '日',
		meanings: ['day', 'sun', 'Japan', 'counter for days'],
		onyomi: ['ニチ', 'ジツ'],
		kunyomi: ['ひ', '-び', '-か'],
		stroke_count: 4,
		jlpt: '5',
		frequency: 1,
		viewer_catalogue_state: { is_saved: true, is_known: true },
	}),
	kanji({
		character: '水',
		meanings: ['water'],
		onyomi: ['スイ'],
		kunyomi: ['みず', 'みず-'],
		stroke_count: 4,
		jlpt: '5',
		frequency: 223,
		viewer_catalogue_state: { is_saved: false, is_known: false },
	}),
	kanji({
		character: '海',
		meanings: ['sea', 'ocean'],
		onyomi: ['カイ'],
		kunyomi: ['うみ'],
		stroke_count: 9,
		jlpt: '4',
		frequency: 200,
		viewer_catalogue_state: { is_saved: true, is_known: false },
	}),
	kanji({
		character: '響',
		meanings: ['echo', 'sound', 'resound', 'ring', 'vibrate'],
		onyomi: ['キョウ'],
		kunyomi: ['ひび.く'],
		stroke_count: 20,
		jlpt: '2',
		frequency: 1111,
	}),
	/** 29 strokes, four kun readings, no level and no frequency rank. */
	kanji({
		character: '鬱',
		meanings: ['gloom', 'depression', 'melancholy', 'luxuriant'],
		onyomi: ['ウツ'],
		kunyomi: ['うっ.する', 'ふさ.ぐ', 'しげ.る', 'しげ.み'],
		stroke_count: 29,
	}),
	/** No readings, no level, no frequency rank. */
	kanji({ character: '々', meanings: ['repetition of kanji (sign)'], stroke_count: 3 }),
	/** Nothing at all, not even a meaning: one of 470 such kanji locally. */
	kanji({ character: '僲', stroke_count: 16 }),
	/** The longest first-three meanings in the local data (107 characters). */
	kanji({
		character: '䌂',
		meanings: [
			'in ancient times_ article for preparing the body for the coffin (something slipped on the hand of the dead)',
		],
		onyomi: ['ケン'],
		stroke_count: 9,
	}),
];

export const wordRows: WordResource[] = [
	word({
		word: '氾濫',
		furigana: 'はんらん',
		meaning: 'overflowing, flooding, inundation',
		word_types: ['Noun', 'Suru verb'],
		jlpt: 'N1',
		viewer_catalogue_state: { is_saved: false, is_known: false },
	}),
	word({
		word: '交流',
		furigana: 'こうりゅう',
		meaning: 'exchange (e.g. cultural), interchange, intercourse',
		word_types: ['Noun', 'Suru verb'],
		jlpt: 'N2',
		viewer_catalogue_state: { is_saved: true, is_known: false },
	}),
	/** A ~200-character meaning. */
	word({
		word: '掛ける',
		furigana: 'かける',
		meaning:
			'to hang up (e.g. a coat); to let hang; to put on (glasses); to cover; to pour; to sprinkle; to multiply; to sit; to call (on the phone); to spend (time, money); to set (a lock); to impose (a tax)',
		word_types: ['Ichidan verb', 'transitive verb'],
		jlpt: 'N4',
	}),
	word({
		word: '遊ぶ',
		furigana: 'あそぶ',
		meaning: 'to play, to enjoy oneself, to have a good time',
		word_types: ['Godan verb'],
		jlpt: '5',
		viewer_catalogue_state: { is_saved: true, is_known: true },
	}),
	/** A long word with no level. */
	word({
		word: '特別警報',
		furigana: 'とくべつけいほう',
		meaning: 'emergency warning',
		word_types: ['noun (common) (futsuumeishi)'],
	}),
	/** No reading, and the gloss attribute that leaks into meanings today. */
	word({
		word: 'ヽ',
		meaning: 'expl, repetition mark in katakana',
		word_types: ['unclassified'],
		jlpt: null,
	}),
	/** The longest reading in the local data (37 characters). */
	word({
		word: '特定独立行政法人等の労働関係に関する法律',
		furigana: 'とくていどくりつぎょうせいほうじんとうのろうどうかんけいにかんするほうりつ',
		meaning: 'Act on Labor Relations of Specified Incorporated Administrative Agencies',
		word_types: ['noun (common) (futsuumeishi)'],
	}),
	/** The longest type list in the local data (231 characters raw). */
	word({
		word: 'シュワシュワ',
		meaning: 'fizzing, bubbling',
		word_types: [
			'noun (common) (futsuumeishi)',
			'adverb (fukushi)',
			"adverb taking the `to' particle",
			"nouns which may take the genitive case particle `no'",
			'adjectival nouns or quasi-adjectives (keiyodoshi)',
			'noun or participle which takes the aux. verb suru',
		],
	}),
];

export const sentenceRows: SentenceResource[] = [
	sentence({ content: '川が氾濫して、町の一部が水につかった。', tatoeba_entry: '4813263' }),
	sentence({ content: '彼は毎朝電車で通学している。', tatoeba_entry: '187450' }),
	/** A 70-character sentence added by a user. */
	sentence({
		content:
			'日本語を勉強し始めてから三年になりますが、漢字の読み方がいまだに覚えられなくて、新聞を読むときはいつも辞書を手元に置いています。',
		user_id: 7,
	}),
	sentence({ content: 'はい。', tatoeba_entry: '2215' }),
	sentence({ content: '嘘！', tatoeba_entry: '1009' }),
];

export const radicalRows: RadicalResource[] = [
	radical({ radical: '氵', meaning: 'water', hiragana: 'さんずい', strokes: 3 }),
	/** 50 of 214 radicals list variant forms in brackets after the glyph. */
	radical({ radical: '乙 (乛、⺄、乚、乙、乀)', meaning: 'second', hiragana: 'おつ\u00a0/ otsu', strokes: 1 }),
	radical({ radical: '木', meaning: 'tree', hiragana: 'き\u00a0/ ki', strokes: 4 }),
	radical({ radical: '隹', meaning: 'short-tailed bird', hiragana: 'ふるとり\u00a0/ furutori', strokes: 8 }),
	radical({ radical: '勹', meaning: 'wrapping', hiragana: 'つつみがまえ\u00a0/ tsutsumigamae', strokes: 2 }),
	/** Null meaning and reading. */
	radical({ radical: '鬯', strokes: 10 }),
];

/** `count` rows cycled from `rows`, with unique ids and uuids, for scroll and sticky-header checks. */
export const repeatRows = <Row extends { id: number; uuid: string }>(rows: readonly Row[], count: number): Row[] =>
	Array.from({ length: count }, (_, index) => {
		const id = 10_000 + index;

		return { ...rows[index % rows.length], id, uuid: uuid(id) };
	});
