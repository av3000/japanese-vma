import { describe, expect, it } from 'vitest';
import {
	getNextSentencesPageParam,
	getSentencesTotal,
	type SentenceListResponse,
} from './useInfiniteSentences';

const createSentenceListResponse = (
	overrides: Partial<SentenceListResponse> = {},
): SentenceListResponse => ({
	items: [
		{
			id: 1,
			uuid: 'sentence-uuid',
			user_id: null,
			tatoeba_entry: '1001',
			content: '私は学生です。',
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

describe('useInfiniteSentences helpers', () => {
	it('returns the next page while more sentence pages exist', () => {
		expect(getNextSentencesPageParam(createSentenceListResponse())).toBe(2);
	});

	it('returns undefined when no more sentence pages exist', () => {
		expect(
			getNextSentencesPageParam(
				createSentenceListResponse({
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
		expect(getSentencesTotal([createSentenceListResponse()])).toBe(11);
	});

	it('returns zero total before pages load', () => {
		expect(getSentencesTotal(undefined)).toBe(0);
	});
});
