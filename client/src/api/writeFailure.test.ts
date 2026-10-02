import { AxiosError, type AxiosResponse } from 'axios';
import { describe, expect, it } from 'vitest';
import { readWriteFailure, WRITE_FAILURE_MESSAGES } from './writeFailure';

const GENERIC = 'Generic failure.';

const httpError = (status: number, data: unknown) => {
	const error = new AxiosError(`Request failed with status code ${status}`, 'ERR_BAD_REQUEST');
	error.response = { status, data, statusText: '', headers: {}, config: {} } as AxiosResponse;
	return error;
};

const problem = (status: number, title: string) => ({ type: 'about:blank', title, status, detail: `${title} detail` });

describe('readWriteFailure', () => {
	it('keeps the 422 field messages and replaces the title with a user-written line', () => {
		const failure = readWriteFailure(
			httpError(422, { title: 'Validation failed', status: 422, errors: { title: ['Too short.'] } }),
			GENERIC,
		);

		expect(failure).toEqual({
			kind: 'validation',
			message: WRITE_FAILURE_MESSAGES.validation,
			errors: { title: ['Too short.'] },
		});
	});

	it('treats a 422 without field errors as an unknown failure', () => {
		expect(readWriteFailure(httpError(422, problem(422, 'Invalid catalogue item')), GENERIC)).toEqual({
			kind: 'unknown',
			message: GENERIC,
		});
	});

	it.each([
		[401, 'unauthenticated', 'Unauthenticated.'],
		[403, 'forbidden', 'Access denied'],
		[404, 'notFound', 'Catalogue not found'],
		[429, 'rateLimited', 'Too Many Attempts.'],
	] as const)('maps a %i to %s without the server title', (status, kind, title) => {
		const failure = readWriteFailure(httpError(status, problem(status, title)), GENERIC);

		expect(failure).toEqual({ kind, message: WRITE_FAILURE_MESSAGES[kind] });
		expect(failure.message).not.toContain(title);
	});

	it('uses the caller message for a 500', () => {
		expect(readWriteFailure(httpError(500, problem(500, 'Catalogue update failed')), GENERIC)).toEqual({
			kind: 'unknown',
			message: GENERIC,
		});
	});

	it('reads the status from the response when the body is not Problem Details', () => {
		expect(readWriteFailure(httpError(403, '<html>Forbidden</html>'), GENERIC)).toEqual({
			kind: 'forbidden',
			message: WRITE_FAILURE_MESSAGES.forbidden,
		});
		expect(readWriteFailure(httpError(502, '<html>Bad gateway</html>'), GENERIC)).toEqual({
			kind: 'unknown',
			message: GENERIC,
		});
	});

	it('reports an axios error without a response as unreachable, never the axios text', () => {
		const failure = readWriteFailure(new AxiosError('Network Error', 'ERR_NETWORK'), GENERIC);

		expect(failure).toEqual({ kind: 'unreachable', message: WRITE_FAILURE_MESSAGES.unreachable });
	});

	it('reports a non-HTTP error as unknown', () => {
		expect(readWriteFailure(new Error('boom'), GENERIC)).toEqual({ kind: 'unknown', message: GENERIC });
		expect(readWriteFailure(null, GENERIC)).toEqual({ kind: 'unknown', message: GENERIC });
	});
});
