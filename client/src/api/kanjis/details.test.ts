import { describe, expect, it } from 'vitest';
import type { KanjiShow200 } from '@/api/generated/model/kanjiShow200';
import { mapKanjiDetail } from './details';

const kanji: KanjiShow200 = {
	id: 1,
	uuid: 'kanji-uuid',
	character: '水',
	onyomi: ['スイ'],
	kunyomi: ['みず'],
	meanings: ['water', 'river', 'liquid', 'flood'],
	nanori: [],
	grade: '1',
	stroke_count: 4,
	jlpt: '5',
	frequency: 2,
	radicals: ['水'],
	radical_parts: ['水'],
};

describe('mapKanjiDetail', () => {
	it('keeps generated kanji fields and adds display values', () => {
		const mapped = mapKanjiDetail(kanji);

		expect(mapped.uuid).toBe('kanji-uuid');
		expect(mapped.character).toBe('水');
		expect(mapped.display.meaning).toBe('water, river, liquid');
		expect(mapped.display.onyomi).toBe('スイ');
		expect(mapped.display.frequency).toBe('2');
	});
});
