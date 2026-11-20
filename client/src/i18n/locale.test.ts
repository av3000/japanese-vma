// @vitest-environment jsdom
import { afterEach, describe, expect, it, vi } from 'vitest';
import { baseLanguage, LOCALE_STORAGE_KEY, pickLocale, readStoredLocale, storeLocale } from './locale';

describe('baseLanguage', () => {
	it('keeps the language and drops the region', () => {
		expect(baseLanguage('da-DK')).toBe('da');
		expect(baseLanguage('PT_br')).toBe('pt');
		expect(baseLanguage('en')).toBe('en');
	});
});

describe('pickLocale', () => {
	const activeCodes = ['en', 'da'];

	it('prefers the stored choice while it is still active', () => {
		expect(pickLocale({ stored: 'da', browserLanguages: ['en-GB'], activeCodes })).toBe('da');
	});

	it('ignores a stored choice that is no longer active', () => {
		expect(pickLocale({ stored: 'sv', browserLanguages: ['da-DK'], activeCodes })).toBe('da');
		expect(pickLocale({ stored: 'sv', browserLanguages: ['de-DE'], activeCodes })).toBe('en');
	});

	it('uses the browser base language when it is active', () => {
		expect(pickLocale({ stored: null, browserLanguages: ['da-DK'], activeCodes })).toBe('da');
	});

	it('walks the browser languages in order', () => {
		expect(pickLocale({ stored: null, browserLanguages: ['de-DE', 'da', 'en'], activeCodes })).toBe('da');
	});

	it('falls back to English when no browser language is active', () => {
		expect(pickLocale({ stored: null, browserLanguages: ['da-DK'], activeCodes: ['en'] })).toBe('en');
		expect(pickLocale({ stored: null, browserLanguages: [], activeCodes })).toBe('en');
	});
});

describe('stored locale', () => {
	afterEach(() => {
		vi.restoreAllMocks();
		window.localStorage.clear();
	});

	it('round-trips through localStorage', () => {
		expect(readStoredLocale()).toBeNull();

		storeLocale('da');

		expect(window.localStorage.getItem(LOCALE_STORAGE_KEY)).toBe('da');
		expect(readStoredLocale()).toBe('da');
	});

	it('survives storage that throws', () => {
		vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
			throw new Error('blocked');
		});
		vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
			throw new Error('blocked');
		});

		expect(readStoredLocale()).toBeNull();
		expect(() => storeLocale('da')).not.toThrow();
	});
});
