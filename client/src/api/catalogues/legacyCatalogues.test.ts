import { describe, expect, it } from 'vitest';
import { stringifyCatalogueTags } from './legacyCatalogues';

describe('catalogue tag serialization', () => {
	it('serializes form tags into the backend hashtag string format', () => {
		expect(stringifyCatalogueTags([])).toBe('');
		expect(stringifyCatalogueTags(['tokyo', '#study', '  note  '])).toBe('#tokyo #study #note');
	});
});
