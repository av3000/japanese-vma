import { describe, expect, it } from 'vitest';
import type { PublicityStatus } from '@/api/generated/model/publicityStatus';
import { isPublic, PUBLICITY, publicityLabel } from './publicity';

describe('PUBLICITY', () => {
	it('matches the backend enum values', () => {
		expect(PUBLICITY).toEqual({ PRIVATE: 0, PUBLIC: 1 });
	});
});

describe('isPublic', () => {
	it('is true only for the public status', () => {
		expect(isPublic(PUBLICITY.PUBLIC)).toBe(true);
		expect(isPublic(PUBLICITY.PRIVATE)).toBe(false);
	});
});

describe('publicityLabel', () => {
	it('names both statuses', () => {
		expect(publicityLabel(PUBLICITY.PUBLIC)).toBe('Public');
		expect(publicityLabel(PUBLICITY.PRIVATE)).toBe('Private');
	});

	it('reads an unknown value as private', () => {
		expect(publicityLabel(7 as PublicityStatus)).toBe('Private');
	});
});
