import { describe, expect, it } from 'vitest';
import type { WordIndex200 } from '@/api/generated/model/wordIndex200';
import {
	applyWordViewerCatalogueState,
	getInfiniteWordsQueryKey,
	getNextWordsPageParam,
	getWordsTotal,
	type WordListResponse,
} from './useInfiniteWords';

const createWordListResponse = (overrides: Partial<WordListResponse> = {}): WordListResponse => ({
	items: [
		{
			id: 1,
			uuid: 'word-uuid',
			word: '学校',
			furigana: 'がっこう',
			jlpt: 'N5',
			meaning: 'school',
			meanings: ['school'],
			word_types: ['noun'],
			writing_elements: ['学校'],
			reading_elements: ['がっこう'],
			word_type: 'noun',
			word_k_ele: '学校',
			furigana_r_ele: 'がっこう',
			sense: '[[["gloss",["school"]]]]',
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

describe('useInfiniteWords helpers', () => {
	it('uses generated word index key with filters', () => {
		expect(getInfiniteWordsQueryKey({ keyword: '学校', per_page: 10 })).toEqual([
			'/words',
			{ keyword: '学校', per_page: 10 },
		]);
	});

	it('returns the next page while more word pages exist', () => {
		expect(getNextWordsPageParam(createWordListResponse())).toBe(2);
	});

	it('returns undefined when no more word pages exist', () => {
		expect(
			getNextWordsPageParam(
				createWordListResponse({
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
		expect(getWordsTotal([createWordListResponse() as WordIndex200])).toBe(11);
	});

	it('returns zero total before pages load', () => {
		expect(getWordsTotal(undefined)).toBe(0);
	});

	it('patches viewer catalogue state for one word inside loaded pages', () => {
		const response = createWordListResponse();

		expect(
			applyWordViewerCatalogueState(
				{
					pages: [response],
					pageParams: [1],
				},
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
