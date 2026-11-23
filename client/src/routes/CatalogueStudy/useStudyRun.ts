import { useCallback, useEffect, useState } from 'react';
import type { StudyDeck } from '@/api/flashcards/deck';
import { useSessionRecorder, type SessionRecorderClient } from '@/api/flashcards/sessions';

export interface UseStudyRunOptions {
	catalogueId: string | undefined;
	deck: StudyDeck | undefined;
	isPlaying: boolean;
	isAuthenticated: boolean;
	/** Test seam for the recorder's requests. */
	client?: SessionRecorderClient;
}

/**
 * One played run of a deck: the key the session component is mounted under, and the recorder
 * that saves it. Every switch into play starts a new run (Start, Back or Forward onto a play
 * URL, a reload), and so does "Study again"; each run is its own saved session.
 *
 * The run cannot be keyed on the deck alone: a deck is cached for good (`staleTime: Infinity`),
 * so playing the same setup twice hands back the same deck, and a deck-keyed recorder would be
 * the first run's, finished, reporting "Results saved." for answers it never sent.
 */
export const useStudyRun = ({ catalogueId, deck, isPlaying, isAuthenticated, client }: UseStudyRunOptions) => {
	// Counted on the way into play, during render (React's documented pattern for state that
	// follows a prop change), so the new run's key is there on the first playing render.
	const [run, setRun] = useState({ no: 0, wasPlaying: isPlaying });
	let runNo = run.no;
	if (run.wasPlaying !== isPlaying) {
		runNo = isPlaying ? run.no + 1 : run.no;
		setRun({ no: runNo, wasPlaying: isPlaying });
	}

	const runKey = isPlaying && deck ? `${deck.config.seed}-${deck.cards.length}-${runNo}` : null;
	const recorder = useSessionRecorder(isAuthenticated, runKey, client);

	useEffect(() => {
		if (runKey !== null && deck && catalogueId) {
			recorder.start(catalogueId, deck.config, deck.cards.length);
		}
	}, [runKey, deck, catalogueId, recorder]);

	/** "Study again": the same deck from the top, as a new run and a new saved session. */
	const restart = useCallback(() => setRun((current) => ({ ...current, no: current.no + 1 })), []);

	return { runKey, recorder, restart };
};
