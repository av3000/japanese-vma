import { useEffect, useRef } from 'react';
import classNames from 'classnames';
import type { StudyCard } from '@/api/flashcards/deck';
import { Button } from '@/components/shared/Button';
import { Cluster, Stack } from '@/components/shared/layout';
import styles from './AnswerFeedback.module.css';

export interface AnswerFeedbackProps {
	card: StudyCard;
	correct: boolean;
	/** The raw accepted answer the learner hit, when correct. */
	matched: string | null;
	/** What the learner typed or picked. */
	given: string;
	/** Accepted answers in Japanese script get `lang="ja"`. */
	japanese: boolean;
	isLast: boolean;
	onNext: () => void;
}

/**
 * The answer side. The verdict is a word plus a mark, never colour alone; every accepted
 * answer is listed with the okurigana dot left in, so the learner sees where the stem ends.
 * Enter advances, as the number keys and the Check button did before it.
 */
export const AnswerFeedback = ({ card, correct, matched, given, japanese, isLast, onNext }: AnswerFeedbackProps) => {
	const actionsRef = useRef<HTMLDivElement>(null);

	// Move focus to the one control that matters now, so Tab and screen readers land on it.
	useEffect(() => {
		actionsRef.current?.querySelector('button')?.focus();
	}, []);

	useEffect(() => {
		const onKeyDown = (event: KeyboardEvent) => {
			if (event.key === 'Enter' && !event.defaultPrevented) {
				event.preventDefault();
				onNext();
			}
		};

		window.addEventListener('keydown', onKeyDown);
		return () => window.removeEventListener('keydown', onKeyDown);
	}, [onNext]);

	return (
		<Stack gap="sm" className={classNames(styles.feedback, correct ? styles.correct : styles.wrong)}>
			<p className={styles.verdict} role="status" aria-live="polite">
				<span aria-hidden="true" className={styles.mark}>
					{correct ? '✓' : '✕'}
				</span>{' '}
				{correct ? 'Correct' : 'Not quite'}
				{!correct && given !== '' && (
					<span className={styles.given}>
						{' '}
						· you answered <span lang={japanese ? 'ja' : undefined}>{given}</span>
					</span>
				)}
			</p>

			<div>
				<p className={styles.label}>{card.acceptedAnswers.length === 1 ? 'Answer' : 'Accepted answers'}</p>
				<Cluster as="ul" gap="xs" className={styles.answers}>
					{card.acceptedAnswers.map((answer) => (
						<li
							key={answer}
							lang={japanese ? 'ja' : undefined}
							className={classNames(styles.answer, answer === matched && styles.matched)}
						>
							{answer}
						</li>
					))}
				</Cluster>
			</div>

			<div ref={actionsRef}>
				<Button type="button" variant="primary" onClick={onNext}>
					{isLast ? 'See results' : 'Next card'}
				</Button>
			</div>
		</Stack>
	);
};

export default AnswerFeedback;
