import { describe, expect, it } from 'vitest';
import { WRITE_FAILURE_MESSAGES } from '@/api/writeFailure';
import { GENERIC_ARTICLE_WRITE_ERROR, readArticleWriteError } from './writes';

const httpError = (status: number, data: unknown) => ({ response: { status, data } });

describe('readArticleWriteError', () => {
	it('keeps the field errors of a 422 and never returns the server title', () => {
		expect(
			readArticleWriteError(httpError(422, { title: 'Validation failed', errors: { title_jp: ['Too short.'] } })),
		).toEqual({
			kind: 'validation',
			message: WRITE_FAILURE_MESSAGES.validation,
			errors: { title_jp: ['Too short.'] },
		});
	});

	it('falls back to the article message for a server failure', () => {
		expect(readArticleWriteError(httpError(500, { title: 'Article creation failed' }))).toEqual({
			kind: 'unknown',
			message: GENERIC_ARTICLE_WRITE_ERROR,
		});
	});
});
