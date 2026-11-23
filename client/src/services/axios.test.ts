// @vitest-environment jsdom
import type { AxiosError } from 'axios';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import axiosInstance from './axios';

vi.mock('@/lib/monitoring/sentry', () => ({
	captureApiError: vi.fn(),
	isSentryEnabled: false,
}));

/**
 * Reaches the rejection half of the response interceptor without a network call.
 */
const rejectWith = async (error: Partial<AxiosError>) => {
	const handlers = (
		axiosInstance.interceptors.response as unknown as { handlers: { rejected: (e: unknown) => unknown }[] }
	).handlers;

	for (const handler of handlers) {
		try {
			await handler.rejected(error);
		} catch {
			// The interceptor always re-rejects; the assertion is on the dispatched event.
		}
	}
};

const unauthorized = (url: string) => ({ response: { status: 401 }, config: { url } }) as Partial<AxiosError>;

describe('axios 401 handling', () => {
	const onUnauthorized = vi.fn();

	beforeEach(() => {
		onUnauthorized.mockClear();
		window.addEventListener('auth:unauthorized', onUnauthorized);
	});

	afterEach(() => {
		window.removeEventListener('auth:unauthorized', onUnauthorized);
	});

	it('escalates a 401 on a protected endpoint', async () => {
		await rejectWith(unauthorized('/articles/1'));

		expect(onUnauthorized).toHaveBeenCalledTimes(1);
	});

	// The generated clients send bare paths against an `/api/v1/` base URL, so the exemption has to
	// match on the last segment rather than on a `/v1/...` literal. The bare-path cases below are
	// fixtures for that segment matching, not leftovers from the legacy routes RET-AUTH-01 retired -
	// dropping them would narrow the guard to a `/v1/` prefix without any test noticing.
	it.each(['/login', '/register', '/logout', '/v1/login', '/v1/register'])(
		'lets %s report its own 401',
		async (url) => {
			await rejectWith(unauthorized(url));

			expect(onUnauthorized).not.toHaveBeenCalled();
		},
	);

	it('ignores non-401 failures', async () => {
		await rejectWith({ response: { status: 500 }, config: { url: '/articles' } } as Partial<AxiosError>);

		expect(onUnauthorized).not.toHaveBeenCalled();
	});
});
