import type { StudyCard } from './deck';

/** One graded answer to one card, as the session records it and the recorder posts it. */
export interface SessionAnswer {
	card: StudyCard;
	/** What the learner typed or picked; empty when nothing was given. */
	given: string;
	correct: boolean;
	/** The raw accepted answer that matched, when correct. */
	matched: string | null;
	responseMs: number;
}
