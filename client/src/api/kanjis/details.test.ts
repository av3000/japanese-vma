import { describe, expect, it } from 'vitest';
import { KANJI_DETAIL_INCLUDE } from './details';

describe('kanji detail query', () => {
	it('asks only for the viewer save state; related lists come from the indexes', () => {
		expect(KANJI_DETAIL_INCLUDE).toBe('viewer_catalogue_state');
	});
});
