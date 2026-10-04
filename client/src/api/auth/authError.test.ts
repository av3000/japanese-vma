import { describe, expect, it } from 'vitest';
import { AUTH_ERROR_MESSAGES, parseAuthError } from './authError';

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

describe('parseAuthError', () => {
	it('maps wrong credentials to a plain sentence, never the axios text', () => {
		const authError = parseAuthError(axiosError(401, invalidCredentials));

		expect(authError).toEqual({ message: AUTH_ERROR_MESSAGES.invalidCredentials, fieldErrors: {} });
		expect(authError.message).not.toContain('Request failed');
	});

	it('places 422 messages under their fields with every message kept', () => {
		const authError = parseAuthError(
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

		expect(authError).toEqual({
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
		const authError = parseAuthError(
			axiosError(
				422,
				validationFailed({
					name: ['The name has already been taken.'],
					email: ['The email has already been taken.'],
				}),
			),
		);

		expect(authError.message).toBeNull();
		expect(authError.fieldErrors.name).toEqual(['The name has already been taken.']);
		expect(authError.fieldErrors.email).toEqual(['The email has already been taken.']);
	});

	it('moves messages for unknown keys into the general message', () => {
		const authError = parseAuthError(axiosError(422, validationFailed({ device_name: ['Too long.'] })));

		expect(authError).toEqual({ message: 'Too long.', fieldErrors: {} });
	});

	it('falls back to a generic prompt when a 422 has no messages at all', () => {
		expect(parseAuthError(axiosError(422, validationFailed({}))).message).toBe(AUTH_ERROR_MESSAGES.checkForm);
	});

	it('maps the rate limit to a wait-and-retry sentence', () => {
		const authError = parseAuthError(
			axiosError(429, { title: 'Too Many Requests', status: 429, detail: 'Too Many Attempts.' }),
		);

		expect(authError).toEqual({ message: AUTH_ERROR_MESSAGES.rateLimited, fieldErrors: {} });
	});

	it('maps a server error to the generic sentence without leaking detail', () => {
		const authError = parseAuthError(
			axiosError(500, {
				title: 'Server error',
				status: 500,
				detail: 'A Server error occurred. Please try again later.',
			}),
		);

		expect(authError).toEqual({ message: AUTH_ERROR_MESSAGES.unknown, fieldErrors: {} });
	});

	it('uses the HTTP status when a proxy returns an HTML error page', () => {
		const authError = parseAuthError(axiosError(502, '<html><body>Bad Gateway</body></html>'));

		expect(authError).toEqual({ message: AUTH_ERROR_MESSAGES.unknown, fieldErrors: {} });
	});

	it('maps an unreachable API (no response) to the connection sentence', () => {
		expect(parseAuthError(new Error('Network Error'))).toEqual({
			message: AUTH_ERROR_MESSAGES.unreachable,
			fieldErrors: {},
		});
	});

	it('survives a non-error rejection value', () => {
		expect(parseAuthError(undefined).message).toBe(AUTH_ERROR_MESSAGES.unreachable);
		expect(parseAuthError(null).message).toBe(AUTH_ERROR_MESSAGES.unreachable);
	});
});
