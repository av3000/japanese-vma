import { Link } from 'react-router-dom';
import type { StudyCard } from '@/api/flashcards/deck';
import { Button } from '@/components/shared/Button';
import { Cluster, Stack } from '@/components/shared/layout';
import styles from './SessionSummary.module.css';

export interface SessionAnswer {
	card: StudyCard;
	given: string;
	correct: boolean;
	matched: string | null;
	responseMs: number;
}

export interface SessionSummaryProps {
	answers: SessionAnswer[];
	/** Prompt text in Japanese script gets `lang="ja"`. */
	promptJapanese: boolean;
	answerJapanese: boolean;
	catalogueTitle: string;
	catalogueHref: string;
	onRestart: () => void;
	onChangeSetup: () => void;
}

/**
 * End of a run: the score, the cards that were missed with their answers, and the ways out.
 * Saving, "retry missed" and "add missed to a catalogue" arrive with FC-FE-04.
 */
export const SessionSummary = ({
	answers,
	promptJapanese,
	answerJapanese,
	catalogueTitle,
	catalogueHref,
	onRestart,
	onChangeSetup,
}: SessionSummaryProps) => {
	const correct = answers.filter((answer) => answer.correct).length;
	const missed = answers.filter((answer) => !answer.correct);

	return (
		<Stack as="section" gap="lg" aria-labelledby="study-summary-title" className={styles.summary}>
			<div>
				<h2 id="study-summary-title" className={styles.title}>
					{correct} of {answers.length} correct
				</h2>
				<p className={styles.lead}>
					{missed.length === 0
						? 'Every card right. Try typed mode, or a bigger deck.'
						: `${missed.length} ${missed.length === 1 ? 'card' : 'cards'} to look at again.`}
				</p>
			</div>

			{missed.length > 0 && (
				<ul className={styles.missed} aria-label="Missed cards">
					{missed.map(({ card, given }) => (
						<li key={card.itemId} className={styles.missedRow}>
							<span className={styles.prompt} lang={promptJapanese ? 'ja' : undefined}>
								{card.promptText}
							</span>
							<span className={styles.answers} lang={answerJapanese ? 'ja' : undefined}>
								{card.acceptedAnswers.join(', ')}
							</span>
							<span className={styles.given}>
								you said: <span lang={answerJapanese ? 'ja' : undefined}>{given || '—'}</span>
							</span>
						</li>
					))}
				</ul>
			)}

			<Cluster gap="sm">
				<Button type="button" variant="primary" onClick={onRestart}>
					Study again
				</Button>
				<Button type="button" variant="ghost" onClick={onChangeSetup}>
					Change setup
				</Button>
				<Link to={catalogueHref} className="tag-link">
					Back to {catalogueTitle}
				</Link>
			</Cluster>
		</Stack>
	);
};

export default SessionSummary;
