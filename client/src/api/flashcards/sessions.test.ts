import { describe, expect, it, vi } from 'vitest';
import type { SessionAnswer } from '@/components/features/flashcards/SessionSummary';
import { kanjiCards } from '@/components/features/flashcards/fixtures';
import { defaultStudyConfig } from './deck';
import { createSessionRecorder, type SessionRecorderClient } from './sessions';

const answer = (index: number, correct: boolean): SessionAnswer => ({
	card: kanjiCards[index],
	given: correct ? kanjiCards[index].displayAnswer : 'fire',
	correct,
	matched: correct ? kanjiCards[index].displayAnswer : null,
	responseMs: 1200.6,
});

const makeClient = (overrides: Partial<SessionRecorderClient> = {}): SessionRecorderClient => ({
	store: vi.fn().mockResolvedValue({ uuid: 's-1' }),
	storeAttempt: vi.fn().mockResolvedValue({}),
	complete: vi.fn().mockResolvedValue({ uuid: 's-1' }),
	...overrides,
});

describe('createSessionRecorder', () => {
	it('creates the session, posts one attempt per card in order, then completes', async () => {
		const calls: string[] = [];
		const client = makeClient({
			store: vi.fn(async () => {
				calls.push('store');
				return { uuid: 's-1' };
			}),
			storeAttempt: vi.fn(async (_uuid, body) => {
				calls.push(`attempt:${body.item_id}`);
				return {};
			}),
			complete: vi.fn(async (uuid, body) => {
				calls.push(`complete:${body.correct_count}`);
				return { uuid } as never;
			}),
		});
		const recorder = createSessionRecorder(client);

		recorder.start('c-1', defaultStudyConfig(), 3);
		recorder.recordAttempt(answer(0, true), 1);
		recorder.recordAttempt(answer(1, false), 1);
		recorder.recordAttempt(answer(2, true), 1);
		const saved = await recorder.complete(2);

		expect(saved).toBe(true);
		expect(calls).toEqual(['store', 'attempt:1', 'attempt:2', 'attempt:3', 'complete:2']);
		expect(recorder.status).toBe('completed');

		expect(client.store).toHaveBeenCalledWith({
			catalogue_uuid: 'c-1',
			prompt: 'character',
			answer: 'meaning',
			mode: 'options',
			script: 'strict',
			card_count: 3,
		});
		expect(client.storeAttempt).toHaveBeenCalledWith('s-1', {
			item_id: 2,
			attempt_no: 1,
			given_answer: 'fire',
			expected_answers: ['water'],
			is_correct: false,
			response_ms: 1201,
		});
	});

	it('keeps going when one attempt fails twice, and still completes', async () => {
		const storeAttempt = vi
			.fn()
			.mockRejectedValueOnce(new Error('offline'))
			.mockRejectedValueOnce(new Error('offline'))
			.mockResolvedValue({});
		const client = makeClient({ storeAttempt });
		const recorder = createSessionRecorder(client);

		recorder.start('c-1', defaultStudyConfig(), 2);
		recorder.recordAttempt(answer(0, true), 1);
		recorder.recordAttempt(answer(1, true), 1);
		const saved = await recorder.complete(2);

		expect(saved).toBe(true);
		// First attempt: original + one retry, both failed and dropped. Second attempt: one call.
		expect(storeAttempt).toHaveBeenCalledTimes(3);
		expect(client.complete).toHaveBeenCalledTimes(1);
	});

	it('reports failure and records nothing when the session cannot be created', async () => {
		const client = makeClient({ store: vi.fn().mockRejectedValue(new Error('500')) });
		const recorder = createSessionRecorder(client);
		const statuses: string[] = [];
		recorder.subscribe((status) => statuses.push(status));

		recorder.start('c-1', defaultStudyConfig(), 1);
		recorder.recordAttempt(answer(0, true), 1);
		const saved = await recorder.complete(1);

		expect(saved).toBe(false);
		expect(statuses).toEqual(['starting', 'failed']);
		expect(client.storeAttempt).not.toHaveBeenCalled();
		expect(client.complete).not.toHaveBeenCalled();
	});

	it('does nothing before start, and starts only once', async () => {
		const client = makeClient();
		const recorder = createSessionRecorder(client);

		recorder.recordAttempt(answer(0, true), 1);
		expect(await recorder.complete(1)).toBe(false);
		expect(client.store).not.toHaveBeenCalled();

		recorder.start('c-1', defaultStudyConfig(), 1);
		recorder.start('c-1', defaultStudyConfig(), 1);
		await recorder.complete(1);

		expect(client.store).toHaveBeenCalledTimes(1);
	});

	it('posts retry rounds with their attempt number after completion', async () => {
		const client = makeClient();
		const recorder = createSessionRecorder(client);

		recorder.start('c-1', defaultStudyConfig(), 1);
		recorder.recordAttempt(answer(0, false), 1);
		await recorder.complete(0);
		recorder.recordAttempt(answer(0, true), 2);
		await recorder.complete(0);

		expect(client.storeAttempt).toHaveBeenLastCalledWith('s-1', expect.objectContaining({ attempt_no: 2 }));
	});
});
