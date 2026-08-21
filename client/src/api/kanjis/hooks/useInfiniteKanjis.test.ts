import { describe, expect, it } from 'vitest';
import type { KanjiIndex200 } from '@/api/generated/model/kanjiIndex200';
import {
	applyKanjiViewerCatalogueState,
	getInfiniteKanjisQueryKey,
	getKanjisTotal,
	getNextKanjisPageParam,
	type KanjiListResponse,
} from './useInfiniteKanjis';

const createKanjiListResponse = (overrides: Partial<KanjiListResponse> = {}): KanjiListResponse => ({
	items: [
		{
			id: 1,
			uuid: 'kanji-uuid',
			character: '水',
			onyomi: ['スイ'],
			kunyomi: ['みず'],
			meanings: ['water', 'river'],
			nanori: [],
			grade: '1',
			stroke_count: 4,
			jlpt: '5',
			frequency: 2,
			radicals: ['水'],
			radical_parts: ['水'],
			viewer_catalogue_state: null,
		},
	],
	pagination: {
		page: 1,
		per_page: 10,
		total: 11,
		last_page: 2,
		has_more: true,
	},
	...overrides,
});

describe('useInfiniteKanjis helpers', () => {
	it('uses generated kanji index key with filters', () => {
		expect(getInfiniteKanjisQueryKey({ keyword: 'water', jlpt: '5', per_page: 10 })).toEqual([
			'/kanjis',
			{ keyword: 'water', jlpt: '5', per_page: 10 },
		]);
	});

	it('returns the next page while more kanji pages exist', () => {
		expect(getNextKanjisPageParam(createKanjiListResponse())).toBe(2);
	});

	it('returns undefined when no more kanji pages exist', () => {
		expect(
			getNextKanjisPageParam(
				createKanjiListResponse({
					pagination: {
						page: 2,
						per_page: 10,
						total: 11,
						last_page: 2,
						has_more: false,
					},
				}),
			),
		).toBeUndefined();
	});

	it('reads total from the first page', () => {
		expect(getKanjisTotal([createKanjiListResponse() as KanjiIndex200])).toBe(11);
	});

	it('returns zero total before pages load', () => {
		expect(getKanjisTotal(undefined)).toBe(0);
	});

	it('patches viewer catalogue state for one kanji in loaded pages', () => {
		const response = createKanjiListResponse();

		expect(
			applyKanjiViewerCatalogueState(
				{ pages: [response], pageParams: [1] },
				1,
				{ is_saved: true, is_known: false },
			),
		).toEqual({
			pages: [
				{
					...response,
					items: [
						{
							...response.items[0],
							viewer_catalogue_state: { is_saved: true, is_known: false },
						},
					],
				},
			],
			pageParams: [1],
		});
	});
});
