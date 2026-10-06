// @vitest-environment jsdom
import { afterEach, describe, expect, it, vi } from 'vitest';
import type { StudyDeck } from '@/api/flashcards/deck';
import type { SessionRecorderClient } from '@/api/flashcards/sessions';
import { renderWithAct } from '@/test/renderWithAct';
import { useStudyRun } from './useStudyRun';

const deck = {
	catalogue: { uuid: 'c', title: 'N5 kanji', type: 6, type_label: 'Kanji' },
	config: { prompt: 'character', answer: 'meaning', mode: 'options', script: 'strict', count: 20, seed: 7 },
	cards: [{ itemId: 1 }, { itemId: 2 }],
	totalItems: 2,
	eligibleItems: 2,
	excludedEmptyAnswerField: 0,
} as unknown as StudyDeck;

const fakeClient = () => {
	let created = 0;
	const client = {
		store: vi.fn(async () => ({ uuid: `session-${++created}` })),
		storeAttempt: vi.fn(async () => ({})),
		complete: vi.fn(async () => ({}) as never),
	} satisfies SessionRecorderClient;
	return client;
};

type Run = ReturnType<typeof useStudyRun>;

const Harness = ({
	isPlaying,
	client,
	onRun,
}: {
	isPlaying: boolean;
	client: SessionRecorderClient;
	onRun: (run: Run) => void;
}) => {
	// The same deck object on every render, as the query cache hands it back.
	onRun(useStudyRun({ catalogueId: 'c', deck, isPlaying, isAuthenticated: true, client }));
	return null;
};

describe('useStudyRun', () => {
	let unmount: (() => Promise<void>) | undefined;

	afterEach(async () => {
		await unmount?.();
		unmount = undefined;
	});

	it('saves a second play of the same cached deck as a new session', async () => {
		const client = fakeClient();
		let run: Run | undefined;
		const onRun = (next: Run) => {
			run = next;
		};

		const rendered = await renderWithAct(<Harness isPlaying client={client} onRun={onRun} />);
		unmount = rendered.unmount;
		const firstKey = run?.runKey;
		await rendered.flush(async () => {
			await run?.recorder.complete(2);
		});
		expect(client.store).toHaveBeenCalledTimes(1);
		expect(run?.recorder.status).toBe('completed');

		// Change setup, then Start again with nothing changed.
		await rendered.rerender(<Harness isPlaying={false} client={client} onRun={onRun} />);
		expect(run?.runKey).toBeNull();
		await rendered.rerender(<Harness isPlaying client={client} onRun={onRun} />);
		await rendered.flush(async () => {});

		expect(run?.runKey).not.toBe(firstKey);
		expect(client.store).toHaveBeenCalledTimes(2);
		expect(run?.recorder.status).toBe('recording');
	});

	it('starts a new session on "Study again"', async () => {
		const client = fakeClient();
		let run: Run | undefined;
		const onRun = (next: Run) => {
			run = next;
		};

		const rendered = await renderWithAct(<Harness isPlaying client={client} onRun={onRun} />);
		unmount = rendered.unmount;
		const firstKey = run?.runKey;

		await rendered.flush(() => run?.restart());

		expect(run?.runKey).not.toBe(firstKey);
		expect(client.store).toHaveBeenCalledTimes(2);
	});
});
