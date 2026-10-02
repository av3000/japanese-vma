import { describe, expect, it } from 'vitest';
import { AUTH_FAILURE_MESSAGES, readAuthFailure } from './authFailure';

/** Shapes an axios rejection the way the interceptor hands it to callers. */
const axiosError = (status: number, data: unknown) =>
	Object.assign(new Error(`Request failed with status code ${status}`), { response: { status, data } });

// The 401 and 422 bodies are copied from the local API (TypedResults::fromError and
// app/Exceptions/Handler.php). The 429, 500 and 502 bodies follow the same handler.
const invalidCredentials = {
	type: 'https://tools.ietf.org/html/rfc7231#section-6.5.2',
	title: 'Invalid credentials',
	status: 401,
	detail: 'The provided email or password is incorrect',
	instance: 'api/v1/login',
	timestamp: '2026-10-02T06:06:54+00:00',
	errorMessage: 'Invalid email or password',
};

const validationFailed = (errors: Record<string, string[]>) => ({
	type: 'https://tools.ietf.org/html/rfc4918#section-11.2',
	title: 'Validation failed',
	status: 422,
	detail: 'One or more validation errors occurred',
	instance: 'api/v1/register',
	timestamp: '2026-10-02T05:53:02+00:00',
	errors,
});

describe('readAuthFailure', () => {
	it('maps wrong credentials to a plain sentence, never the axios text', () => {
		const failure = readAuthFailure(axiosError(401, invalidCredentials));

		expect(failure).toEqual({ message: AUTH_FAILURE_MESSAGES.invalidCredentials, fieldErrors: {} });
		expect(failure.message).not.toContain('Request failed');
	});

	it('places 422 messages under their fields with every message kept', () => {
		const failure = readAuthFailure(
			axiosError(
				422,
				validationFailed({
					email: ['The email has already been taken.'],
					password: [
						'The password must be at least 8 characters.',
						'The password field must contain at least one number.',
					],
				}),
			),
		);

		expect(failure).toEqual({
			message: null,
			fieldErrors: {
				email: ['The email has already been taken.'],
				password: [
					'The password must be at least 8 characters.',
					'The password field must contain at least one number.',
				],
			},
		});
	});

	it('handles a taken email and a taken username together', () => {
		const failure = readAuthFailure(
			axiosError(
				422,
				validationFailed({
					name: ['The name has already been taken.'],
					email: ['The email has already been taken.'],
				}),
			),
		);

		expect(failure.message).toBeNull();
		expect(failure.fieldErrors.name).toEqual(['The name has already been taken.']);
		expect(failure.fieldErrors.email).toEqual(['The email has already been taken.']);
	});

	it('moves messages for unknown keys into the general message', () => {
		const failure = readAuthFailure(axiosError(422, validationFailed({ device_name: ['Too long.'] })));

		expect(failure).toEqual({ message: 'Too long.', fieldErrors: {} });
	});

	it('falls back to a generic prompt when a 422 has no messages at all', () => {
		expect(readAuthFailure(axiosError(422, validationFailed({}))).message).toBe(AUTH_FAILURE_MESSAGES.checkForm);
	});

	it('maps the rate limit to a wait-and-retry sentence', () => {
		const failure = readAuthFailure(
			axiosError(429, { title: 'Too Many Requests', status: 429, detail: 'Too Many Attempts.' }),
		);

		expect(failure).toEqual({ message: AUTH_FAILURE_MESSAGES.rateLimited, fieldErrors: {} });
	});

	it('maps a server error to the generic sentence without leaking detail', () => {
		const failure = readAuthFailure(
			axiosError(500, {
				title: 'Server error',
				status: 500,
				detail: 'A Server error occurred. Please try again later.',
			}),
		);

		expect(failure).toEqual({ message: AUTH_FAILURE_MESSAGES.unknown, fieldErrors: {} });
	});

	it('uses the HTTP status when a proxy returns an HTML error page', () => {
		const failure = readAuthFailure(axiosError(502, '<html><body>Bad Gateway</body></html>'));

		expect(failure).toEqual({ message: AUTH_FAILURE_MESSAGES.unknown, fieldErrors: {} });
	});

	it('maps an unreachable API (no response) to the connection sentence', () => {
		expect(readAuthFailure(new Error('Network Error'))).toEqual({
			message: AUTH_FAILURE_MESSAGES.unreachable,
			fieldErrors: {},
		});
	});

	it('survives a non-error rejection value', () => {
		expect(readAuthFailure(undefined).message).toBe(AUTH_FAILURE_MESSAGES.unreachable);
		expect(readAuthFailure(null).message).toBe(AUTH_FAILURE_MESSAGES.unreachable);
	});
});
