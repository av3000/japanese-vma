import { useEffect, useMemo, useState } from 'react';
import type { CompleteStudySessionRequest } from '@/api/generated/model/completeStudySessionRequest';
import type { StoreStudyAttemptRequest } from '@/api/generated/model/storeStudyAttemptRequest';
import type { StoreStudySessionRequest } from '@/api/generated/model/storeStudySessionRequest';
import type { StudySessionResource } from '@/api/generated/model/studySessionResource';
import type { UuidCreatedResource } from '@/api/generated/model/uuidCreatedResource';
import {
	studySessionComplete,
	studySessionStore,
	studySessionStoreAttempt,
} from '@/api/generated/study-session/study-session';
import type { SessionAnswer } from '@/components/features/flashcards/SessionSummary';
import type { StudyConfig } from './deck';

/**
 * Saves a signed-in learner's run (epic #413). The session is created when the run starts;
 * each answer is queued and posted in order without blocking the next card; the final score
 * is posted after the queue drains. A failed request is retried once and then dropped, so
 * a slow or broken network never interrupts play. Visitors never call any of this.
 */

export type SaveStatus = 'disabled' | 'starting' | 'recording' | 'completed' | 'failed';

export interface SessionRecorderClient {
	store: (body: StoreStudySessionRequest) => Promise<UuidCreatedResource>;
	storeAttempt: (uuid: string, body: StoreStudyAttemptRequest) => Promise<unknown>;
	complete: (uuid: string, body: CompleteStudySessionRequest) => Promise<StudySessionResource>;
}

export interface SessionRecorder {
	start: (catalogueUuid: string, config: StudyConfig, cardCount: number) => void;
	recordAttempt: (answer: SessionAnswer, attemptNo: number) => void;
	complete: (correctCount: number) => Promise<boolean>;
	readonly status: SaveStatus;
	/** Subscribe to status changes; returns the unsubscribe function. */
	subscribe: (listener: (status: SaveStatus) => void) => () => void;
}

const defaultClient: SessionRecorderClient = {
	store: studySessionStore,
	storeAttempt: studySessionStoreAttempt,
	complete: studySessionComplete,
};

const toAttemptBody = (answer: SessionAnswer, attemptNo: number): StoreStudyAttemptRequest => ({
	item_id: answer.card.itemId,
	attempt_no: attemptNo,
	given_answer: answer.given === '' ? null : answer.given,
	expected_answers: answer.card.acceptedAnswers,
	is_correct: answer.correct,
	response_ms: Math.max(0, Math.round(answer.responseMs)),
});

const withOneRetry = async <T>(request: () => Promise<T>): Promise<T | undefined> => {
	try {
		return await request();
	} catch {
		try {
			return await request();
		} catch {
			return undefined;
		}
	}
};

export const createSessionRecorder = (client: SessionRecorderClient = defaultClient): SessionRecorder => {
	let status: SaveStatus = 'disabled';
	let sessionUuid: string | null = null;
	let started: Promise<void> | null = null;
	let queue: Promise<void> = Promise.resolve();
	const listeners = new Set<(status: SaveStatus) => void>();

	const setStatus = (next: SaveStatus) => {
		status = next;
		listeners.forEach((listener) => listener(next));
	};

	const enqueue = (work: () => Promise<void>) => {
		queue = queue.then(work, work);
	};

	return {
		get status() {
			return status;
		},
		subscribe(listener) {
			listeners.add(listener);
			return () => listeners.delete(listener);
		},
		start(catalogueUuid, config, cardCount) {
			if (started) return;
			setStatus('starting');
			started = withOneRetry(() =>
				client.store({
					catalogue_uuid: catalogueUuid,
					prompt: config.prompt,
					answer: config.answer,
					mode: config.mode,
					script: config.script,
					card_count: cardCount,
				}),
			).then((created) => {
				sessionUuid = created?.uuid ?? null;
				setStatus(sessionUuid ? 'recording' : 'failed');
			});
		},
		recordAttempt(answer, attemptNo) {
			// After completion the server answers 409 to first-pass attempts; do not even ask.
			if (!started || (status === 'completed' && attemptNo === 1)) return;
			enqueue(async () => {
				await started;
				if (!sessionUuid) return;
				await withOneRetry(() => client.storeAttempt(sessionUuid as string, toAttemptBody(answer, attemptNo)));
			});
		},
		async complete(correctCount) {
			if (!started) return false;
			await started;
			await queue;
			if (!sessionUuid) return false;

			const result = await withOneRetry(() =>
				client.complete(sessionUuid as string, { correct_count: correctCount }),
			);
			setStatus(result ? 'completed' : 'failed');
			return result !== undefined;
		},
	};
};

/**
 * One recorder per played deck, exposed with its live status. `enabled` is the learner's
 * sign-in state: a visitor's recorder stays `disabled` and records nothing. A change of
 * `deckKey` (another seed or configuration) starts over with a fresh recorder, so every
 * deck the learner plays becomes its own saved session.
 */
export const useSessionRecorder = (enabled: boolean, deckKey: string | null, client?: SessionRecorderClient) => {
	// eslint-disable-next-line react-hooks/exhaustive-deps -- deckKey is the reset trigger, not a value read inside
	const recorder = useMemo(() => createSessionRecorder(client), [deckKey, client]);
	const [status, setStatus] = useState<SaveStatus>(recorder.status);

	useEffect(() => {
		setStatus(recorder.status);
		return recorder.subscribe(setStatus);
	}, [recorder]);

	return useMemo(
		() => ({
			status: enabled ? status : ('disabled' as const),
			start: (catalogueUuid: string, config: StudyConfig, cardCount: number) => {
				if (enabled) recorder.start(catalogueUuid, config, cardCount);
			},
			recordAttempt: (answer: SessionAnswer, attemptNo: number) => {
				if (enabled) recorder.recordAttempt(answer, attemptNo);
			},
			complete: (correctCount: number) => (enabled ? recorder.complete(correctCount) : Promise.resolve(false)),
		}),
		[enabled, recorder, status],
	);
};
