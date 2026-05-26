import { describe, expect, it } from 'vitest';
import type { RadicalShow200 } from '@/api/generated/model/radicalShow200';
import { mapRadicalDetail } from './details';

describe('mapRadicalDetail', () => {
	it('keeps related kanjis as an array when present', () => {
		const radical = {
			id: 7,
			uuid: 'radical-uuid',
			radical: '水',
			hiragana: 'みず',
			meaning: 'water',
			strokes: 4,
			kanjis: [
				{
					uuid: 'kanji-uuid',
					character: '海',
					onyomi: 'カイ',
					kunyomi: 'うみ',
					meanings: 'sea',
					nanori: '',
					grade: '2',
					stroke_count: '9',
					jlpt: '3',
					frequency: '200',
					radicals: '水',
					radical_parts: '氵毎',
				},
			],
		} as RadicalShow200;

		expect(mapRadicalDetail(radical).kanjis).toHaveLength(1);
	});

	it('normalizes missing related kanjis to an empty array', () => {
		const radical = {
			id: 8,
			uuid: 'radical-uuid-2',
			radical: '火',
			hiragana: 'ひ',
			meaning: 'fire',
			strokes: 4,
		} as RadicalShow200;

		expect(mapRadicalDetail(radical).kanjis).toEqual([]);
	});
});
