import { describe, expect, it, vi } from 'vitest';
import { setApiFieldErrors, getFieldErrorMessages } from './formErrors';

type Values = { title: string; content: string; tags: string[] };

const FIELDS = ['title', 'content', 'tags'] as const;

describe('setApiFieldErrors', () => {
	it('sets errors in form order and focuses only the first field', () => {
		const setError = vi.fn();

		setApiFieldErrors<Values>(setError, { tags: ['Bad tag.'], content: ['Too short.'] }, FIELDS);

		expect(setError.mock.calls).toEqual([
			[
				'content',
				{ type: 'server', message: 'Too short.', types: { server: ['Too short.'] } },
				{ shouldFocus: true },
			],
			['tags', { type: 'server', message: 'Bad tag.', types: { server: ['Bad tag.'] } }, { shouldFocus: false }],
		]);
	});

	it('maps server names and folds indexed entries into one field', () => {
		const setError = vi.fn();

		setApiFieldErrors<Values>(
			setError,
			{ 'hashtags.0': ['Tag 1 is too long.'], 'hashtags.3': ['Tag 4 is too long.', 'Tag 1 is too long.'] },
			FIELDS,
			{ hashtags: 'tags' },
		);

		expect(setError).toHaveBeenCalledTimes(1);
		expect(setError).toHaveBeenCalledWith(
			'tags',
			{
				type: 'server',
				message: 'Tag 1 is too long.',
				types: { server: ['Tag 1 is too long.', 'Tag 4 is too long.'] },
			},
			{ shouldFocus: true },
		);
	});

	it('returns messages that belong to no field and skips empty lists', () => {
		const setError = vi.fn();

		const unmatched = setApiFieldErrors<Values>(
			setError,
			{ fields: ['At least one field must be provided.'], title: [], description: ['Too long.'] },
			FIELDS,
			{ description: 'summary' as never },
		);

		expect(unmatched).toEqual(['At least one field must be provided.', 'Too long.']);
		expect(setError).not.toHaveBeenCalled();
	});
});

describe('getFieldErrorMessages', () => {
	it('lists every server message, or the single client message', () => {
		expect(getFieldErrorMessages(undefined)).toEqual([]);
		expect(getFieldErrorMessages({ type: 'too_small', message: 'Too short.' })).toEqual(['Too short.']);
		expect(getFieldErrorMessages({ type: 'server', message: 'A.', types: { server: ['A.', 'B.'] } })).toEqual([
			'A.',
			'B.',
		]);
	});

	it('collects per-item and whole-list errors of an array field', () => {
		expect(getFieldErrorMessages([undefined, { type: 'too_big', message: 'Too long.' }])).toEqual(['Too long.']);
		expect(getFieldErrorMessages({ root: { type: 'too_big', message: 'Too many tags.' } })).toEqual([
			'Too many tags.',
		]);
	});
});
