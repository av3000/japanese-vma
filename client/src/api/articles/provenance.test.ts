import { describe, expect, it } from 'vitest';
import { importedSourceOf } from './provenance';

const nhk = { key: 'nhk-news', name: 'NHK News', homepage_url: 'https://news.web.nhk/newsweb' };

describe('importedSourceOf', () => {
	it('returns the source of an imported article', () => {
		expect(importedSourceOf({ origin: 'imported', source: nhk })).toBe(nhk);
	});

	it('returns nothing for a user article, even if a source were present', () => {
		expect(importedSourceOf({ origin: 'user', source: nhk })).toBeNull();
	});

	it('returns nothing for an import whose source was removed', () => {
		expect(importedSourceOf({ origin: 'imported', source: null })).toBeNull();
	});
});
