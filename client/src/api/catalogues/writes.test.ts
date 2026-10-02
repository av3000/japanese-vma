import { describe, expect, it } from 'vitest';
import { WRITE_FAILURE_MESSAGES } from '@/api/writeFailure';
import { GENERIC_CATALOGUE_WRITE_ERROR, readCatalogueWriteError } from './writes';

const httpError = (status: number, data: unknown) => ({ response: { status, data } });

describe('readCatalogueWriteError', () => {
	it('keeps the field errors of a 422 and never returns the server title', () => {
		expect(
			readCatalogueWriteError(httpError(422, { title: 'Validation failed', errors: { title: ['Too short.'] } })),
		).toEqual({
			kind: 'validation',
			message: WRITE_FAILURE_MESSAGES.validation,
			errors: { title: ['Too short.'] },
		});
	});

	it('falls back to the catalogue message for a server failure', () => {
		expect(readCatalogueWriteError(httpError(500, { title: 'Catalogue creation failed' }))).toEqual({
			kind: 'unknown',
			message: GENERIC_CATALOGUE_WRITE_ERROR,
		});
	});
});
