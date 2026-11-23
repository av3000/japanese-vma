import { AxiosError, AxiosHeaders } from 'axios';
import { describe, expect, it } from 'vitest';
import { unavailableMessage } from './DetailUnavailable';

const httpError = (status: number) =>
	new AxiosError('Request failed', 'ERR_BAD_REQUEST', undefined, undefined, {
		status,
		statusText: '',
		headers: {},
		config: { headers: new AxiosHeaders() },
		data: { title: 'Kanji not found', detail: 'Kanji 4d98c557 not found' },
	});

describe('unavailableMessage', () => {
	it.each(['kanji', 'word', 'radical', 'sentence'] as const)('names a missing %s in its own words', (noun) => {
		expect(unavailableMessage(httpError(404), noun)).toBe(`This ${noun} doesn't exist. It may have been deleted.`);
	});

	it('never passes the server text through', () => {
		const message = unavailableMessage(httpError(500), 'kanji');

		expect(message).toBe("This kanji couldn't be loaded. Please try again.");
		expect(message).not.toContain('4d98c557');
	});

	it('explains an unreachable server the same way for every noun', () => {
		const offline = new AxiosError('Network Error', 'ERR_NETWORK');

		expect(unavailableMessage(offline, 'radical')).toBe(
			"We couldn't reach the server. Check your connection and try again.",
		);
	});
});
