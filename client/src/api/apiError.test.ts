import { AxiosError, type AxiosResponse } from 'axios';
import { describe, expect, it } from 'vitest';
import { API_ERROR_MESSAGES, parseApiError } from './apiError';

const httpError = (status: number, data: unknown) => {
	const error = new AxiosError(`Request failed with status code ${status}`, 'ERR_BAD_REQUEST');
	error.response = { status, data, statusText: '', headers: {}, config: {} } as AxiosResponse;
	return error;
};

const problem = (status: number, title: string) => ({ type: 'about:blank', title, status, detail: `${title} detail` });

describe('parseApiError', () => {
	it('keeps the 422 field messages and replaces the title with a user-written line', () => {
		const apiError = parseApiError(
			httpError(422, { title: 'Validation failed', status: 422, errors: { title: ['Too short.'] } }),
		);

		expect(apiError).toEqual({
			kind: 'validation',
			message: API_ERROR_MESSAGES.validation,
			errors: { title: ['Too short.'] },
		});
	});

	it('treats a 422 without field errors as an unknown error', () => {
		expect(parseApiError(httpError(422, problem(422, 'Invalid catalogue item')))).toEqual({
			kind: 'unknown',
			message: API_ERROR_MESSAGES.unknown,
		});
	});

	it.each([
		[401, 'unauthenticated', 'Unauthenticated.'],
		[403, 'forbidden', 'Access denied'],
		[404, 'notFound', 'Catalogue not found'],
		[429, 'rateLimited', 'Too Many Attempts.'],
	] as const)('maps a %i to %s without the server title', (status, kind, title) => {
		const apiError = parseApiError(httpError(status, problem(status, title)));

		expect(apiError).toEqual({ kind, message: API_ERROR_MESSAGES[kind] });
		expect(apiError.message).not.toContain(title);
	});

	it('shows the generic message for a 500', () => {
		expect(parseApiError(httpError(500, problem(500, 'Catalogue update failed')))).toEqual({
			kind: 'unknown',
			message: API_ERROR_MESSAGES.unknown,
		});
	});

	it('reads the status from the response when the body is not Problem Details', () => {
		expect(parseApiError(httpError(403, '<html>Forbidden</html>'))).toEqual({
			kind: 'forbidden',
			message: API_ERROR_MESSAGES.forbidden,
		});
		expect(parseApiError(httpError(502, '<html>Bad gateway</html>'))).toEqual({
			kind: 'unknown',
			message: API_ERROR_MESSAGES.unknown,
		});
	});

	it('reports an axios error without a response as unreachable, never the axios text', () => {
		expect(parseApiError(new AxiosError('Network Error', 'ERR_NETWORK'))).toEqual({
			kind: 'unreachable',
			message: API_ERROR_MESSAGES.unreachable,
		});
	});

	it('reports a non-HTTP error as unknown', () => {
		expect(parseApiError(new Error('boom'))).toEqual({ kind: 'unknown', message: API_ERROR_MESSAGES.unknown });
		expect(parseApiError(null)).toEqual({ kind: 'unknown', message: API_ERROR_MESSAGES.unknown });
	});
});
