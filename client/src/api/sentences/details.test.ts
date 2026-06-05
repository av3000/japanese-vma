import { describe, expect, it } from 'vitest';
import { mapSentenceDetail, type SentenceDetailResponse } from './details';

const createSentenceDetail = (
	overrides: Partial<SentenceDetailResponse> = {},
): SentenceDetailResponse => ({
	id: 10,
	uuid: 'sentence-uuid',
	user_id: null,
	tatoeba_entry: '5005',
	content: '水を飲みます。',
	kanjis: [
		{
			uuid: 'kanji-uuid',
			character: '水',
			onyomi: 'スイ',
			kunyomi: 'みず',
			meanings: 'water',
			nanori: '',
			grade: '1',
			stroke_count: '4',
			jlpt: '5',
			frequency: '2',
			radicals: '水',
			radical_parts: '水',
		},
	],
	words: [],
	...overrides,
});

describe('mapSentenceDetail', () => {
	it('keeps B7 sentence detail fields and exposes kanjis', () => {
		const mapped = mapSentenceDetail(createSentenceDetail());

		expect(mapped.id).toBe(10);
		expect(mapped.uuid).toBe('sentence-uuid');
		expect(mapped.content).toBe('水を飲みます。');
		expect(mapped.kanjis).toHaveLength(1);
		expect(mapped.kanjis[0].character).toBe('水');
	});

	it('keeps transitional words as an empty array', () => {
		const mapped = mapSentenceDetail(createSentenceDetail({ words: [] }));

		expect(mapped.words).toEqual([]);
	});
});
