import { Link, useLocation } from 'react-router-dom';
import type { StudyCard } from '@/api/flashcards/deck';
import type { SaveStatus } from '@/api/flashcards/sessions';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Button } from '@/components/shared/Button';
import { Cluster, Stack } from '@/components/shared/layout';
import type { SavedListType } from '@/shared/constants/enums';
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
	/** 1 for the first pass, 2 and up for "retry missed" rounds. */
	attemptNo: number;
	/** Prompt text in Japanese script gets `lang="ja"`. */
	promptJapanese: boolean;
	answerJapanese: boolean;
	catalogueTitle: string;
	catalogueHref: string;
	/** Where the recorder got to; `disabled` means the learner is a visitor. */
	saveStatus: SaveStatus;
	/** The custom catalogue type the missed items can be saved into; null hides the bookmarks. */
	bookmarkCatalogueType: SavedListType | null;
	onRetryMissed: () => void;
	onRestart: () => void;
	onChangeSetup: () => void;
}

const SAVE_LINE: Record<Exclude<SaveStatus, 'disabled'>, string> = {
	starting: 'Saving your results…',
	recording: 'Saving your results…',
	completed: 'Results saved.',
	failed: 'Your results could not be saved this time.',
};

/**
 * End of a run: the score, the cards that were missed with their answers and a bookmark
 * to put each one in a catalogue, and the ways on: retry the missed cards (same session,
 * next attempt number), run the deck again, change the setup, or go back.
 */
export const SessionSummary = ({
	answers,
	attemptNo,
	promptJapanese,
	answerJapanese,
	catalogueTitle,
	catalogueHref,
	saveStatus,
	bookmarkCatalogueType,
	onRetryMissed,
	onRestart,
	onChangeSetup,
}: SessionSummaryProps) => {
	// Same shape PrivateRoute sends, so a Login that honours `state.from` returns the learner here.
	const location = useLocation();
	const correct = answers.filter((answer) => answer.correct).length;
	const missed = answers.filter((answer) => !answer.correct);
	const canBookmark = saveStatus !== 'disabled' && bookmarkCatalogueType !== null;

	return (
		<Stack as="section" gap="lg" aria-labelledby="study-summary-title" className={styles.summary}>
			<div>
				<h2 id="study-summary-title" className={styles.title}>
					{correct} of {answers.length} correct
					{attemptNo > 1 && <span className={styles.round}> · retry round {attemptNo - 1}</span>}
				</h2>
				<p className={styles.lead}>
					{missed.length === 0
						? 'Every card right. Try typed mode, or a bigger deck.'
						: `${missed.length} ${missed.length === 1 ? 'card' : 'cards'} to look at again.`}
				</p>
				<p className={styles.save} role="status">
					{saveStatus === 'disabled' ? (
						<>
							<Link to="/login" state={{ from: location }}>
								Sign in
							</Link>{' '}
							to save your progress.
						</>
					) : (
						SAVE_LINE[saveStatus]
					)}
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
							{canBookmark && (
								<span className={styles.bookmark}>
									<AuthorizedBookmarkWidget
										entityId={card.itemId}
										instanceObjectType={bookmarkCatalogueType}
										modalTitle={`Save ${card.promptText} to a catalogue`}
										loadOnMount={false}
									/>
								</span>
							)}
						</li>
					))}
				</ul>
			)}

			<Cluster gap="sm">
				{missed.length > 0 && (
					<Button type="button" variant="primary" onClick={onRetryMissed}>
						Retry missed ({missed.length})
					</Button>
				)}
				<Button type="button" variant={missed.length > 0 ? 'outline' : 'primary'} onClick={onRestart}>
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
