import { QueryClient } from '@tanstack/react-query';
import { describe, expect, it, vi } from 'vitest';
import {
	canMutateSentence,
	evictSentenceCaches,
	isImportedSentence,
	reconcileSentenceCaches,
	sentenceDetailQueryKeys,
	type SentenceWriteResponse,
} from './authoring';

const createSentence = (overrides: Partial<SentenceWriteResponse> = {}): SentenceWriteResponse => ({
	id: 77,
	uuid: 'sentence-uuid',
	user_id: 5,
	tatoeba_entry: null,
	content: '水を飲みます。',
	kanjis: [],
	words: [],
	...overrides,
});

const owner = { id: 5, isAdmin: false };
const admin = { id: 9, isAdmin: true };
const stranger = { id: 42, isAdmin: false };

describe('isImportedSentence', () => {
	it('treats a null author as imported and any author id as authored', () => {
		expect(isImportedSentence(createSentence({ user_id: null }))).toBe(true);
		expect(isImportedSentence(createSentence({ user_id: 0 }))).toBe(false);
		expect(isImportedSentence(createSentence({ user_id: 5 }))).toBe(false);
	});
});

describe('canMutateSentence', () => {
	it('allows the author and any admin on an authored sentence', () => {
		expect(canMutateSentence(owner, createSentence())).toBe(true);
		expect(canMutateSentence(admin, createSentence())).toBe(true);
	});

	it('refuses guests and non-authors', () => {
		expect(canMutateSentence(null, createSentence())).toBe(false);
		expect(canMutateSentence(undefined, createSentence())).toBe(false);
		expect(canMutateSentence(stranger, createSentence())).toBe(false);
	});

	it('refuses imported sentences even for an admin, matching SentencePolicy', () => {
		const imported = createSentence({ user_id: null });

		expect(canMutateSentence(admin, imported)).toBe(false);
		expect(canMutateSentence(owner, imported)).toBe(false);
	});
});

describe('sentenceDetailQueryKeys', () => {
	it('covers both the UUID and the legacy numeric detail identifier', () => {
		expect(sentenceDetailQueryKeys(createSentence())).toEqual([['/sentences/sentence-uuid'], ['/sentences/77']]);
	});
});

describe('reconcileSentenceCaches', () => {
	it('writes the response into both detail entries and invalidates the lists', () => {
		const queryClient = new QueryClient();
		const invalidate = vi.spyOn(queryClient, 'invalidateQueries').mockReturnValue(Promise.resolve());

		queryClient.setQueryData(['/sentences/sentence-uuid'], createSentence({ content: 'stale by uuid' }));
		queryClient.setQueryData(['/sentences/77'], createSentence({ content: 'stale by id' }));

		const updated = createSentence({ content: '火を見ます。' });
		reconcileSentenceCaches(queryClient, updated);

		expect(queryClient.getQueryData(['/sentences/sentence-uuid'])).toEqual(updated);
		expect(queryClient.getQueryData(['/sentences/77'])).toEqual(updated);
		expect(invalidate).toHaveBeenCalledWith({ queryKey: ['/sentences'] });
	});
});

describe('evictSentenceCaches', () => {
	it('removes both detail entries and invalidates the lists', () => {
		const queryClient = new QueryClient();
		const invalidate = vi.spyOn(queryClient, 'invalidateQueries').mockReturnValue(Promise.resolve());

		queryClient.setQueryData(['/sentences/sentence-uuid'], createSentence());
		queryClient.setQueryData(['/sentences/77'], createSentence());

		evictSentenceCaches(queryClient, createSentence());

		expect(queryClient.getQueryData(['/sentences/sentence-uuid'])).toBeUndefined();
		expect(queryClient.getQueryData(['/sentences/77'])).toBeUndefined();
		expect(invalidate).toHaveBeenCalledWith({ queryKey: ['/sentences'] });
	});
});
