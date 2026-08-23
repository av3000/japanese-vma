import { describe, expect, it } from 'vitest';
import type { KanjiDetailResource } from '@/api/generated/model/kanjiDetailResource';
import { mapKanjiDetail } from './details';

const kanji: KanjiDetailResource = {
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
	viewer_catalogue_state: null,
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

	it('normalizes omitted related collections to empty UI data', () => {
		const mapped = mapKanjiDetail(kanji);

		expect(mapped.related).toEqual({
			words: [],
			wordTotal: 0,
			sentences: [],
			sentenceTotal: 0,
			articles: [],
			articleTotal: 0,
		});
	});

	it('maps lean related articles and uses the preview length as the total', () => {
		const mapped = mapKanjiDetail({
			...kanji,
			articles: [
				{
					id: 701,
					uuid: 'article-uuid',
					title_jp: '水の記事',
					hashtags: [{ id: 1, content: '#water' }],
					views_total: 3,
					likes_total: 2,
					comments_total: 1,
				},
			],
		});

		expect(mapped.related.articles[0]).toMatchObject({
			uuid: 'article-uuid',
			views_total: 3,
			likes_total: 2,
			comments_total: 1,
		});
		expect(mapped.related.articleTotal).toBe(1);
	});
});
