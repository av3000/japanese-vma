import { describe, expect, it } from 'vitest';
import type { WordShow200 } from '@/api/generated/model/wordShow200';
import { mapWordDetail } from './details';

const baseWord: WordShow200 = {
	id: 42,
	uuid: 'word-uuid',
	word: '水',
	furigana: 'みず',
	jlpt: 'N5',
	meaning: 'water',
	meanings: ['water'],
	word_types: ['noun'],
	writing_elements: ['水'],
	reading_elements: ['みず'],
	word_type: 'noun',
	word_k_ele: '水',
	furigana_r_ele: 'みず',
	sense: null,
	viewer_catalogue_state: null,
};

describe('mapWordDetail', () => {
	it('keeps typed related data', () => {
		const relatedKanji = {
			id: 1,
			uuid: 'kanji-uuid',
			character: '水',
			onyomi: ['スイ'],
			kunyomi: ['みず'],
			meanings: ['water'],
			nanori: [],
			grade: '1',
			stroke_count: 4,
			jlpt: '5',
			frequency: 2,
			radicals: ['水'],
			radical_parts: ['水'],
			viewer_catalogue_state: null,
		};
		const relatedArticle = {
			id: 2,
			uuid: 'article-uuid',
			title_jp: '水の記事',
			hashtags: [],
			views_total: 3,
			likes_total: 2,
			comments_total: 1,
		};

		const mapped = mapWordDetail({ ...baseWord, kanjis: [relatedKanji], articles: [relatedArticle] });

		expect(mapped.kanjis).toEqual([relatedKanji]);
		expect(mapped.articles).toEqual([relatedArticle]);
	});

	it('normalizes omitted optional relations', () => {
		expect(mapWordDetail(baseWord).kanjis).toEqual([]);
		expect(mapWordDetail(baseWord).articles).toEqual([]);
	});
});
