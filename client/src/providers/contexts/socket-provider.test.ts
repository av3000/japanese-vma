import { describe, expect, it } from 'vitest';
import { shouldStartWebSocket } from './socket-provider';

describe('shouldStartWebSocket', () => {
	it('keeps anonymous homepage sessions disconnected', () => {
		expect(
			shouldStartWebSocket({
				isConfigured: true,
				isAuthenticated: false,
				token: null,
			}),
		).toBe(false);
	});
});
