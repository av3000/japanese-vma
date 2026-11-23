import { useEffect, useRef } from 'react';
import classNames from 'classnames';
import type { StudyCard } from '@/api/flashcards/deck';
import { Button } from '@/components/shared/Button';
import { Cluster, Stack } from '@/components/shared/layout';
import { useLatest } from '@/hooks/useLatest';
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

/** Controls whose own Enter behaviour (follow, press, submit) must win over "next card". */
const CONTROL_SELECTOR =
	'a[href], button, input, select, textarea, summary, [contenteditable="true"], [role="button"], [role="link"]';

/**
 * The answer side. The verdict is a word plus a mark, never colour alone; every accepted
 * answer is listed with the okurigana dot left in, so the learner sees where the stem ends.
 * Enter advances from anywhere except another control: with focus on a link, a button or a
 * field elsewhere on the page, Enter keeps its own meaning.
 */
export const AnswerFeedback = ({ card, correct, matched, given, japanese, isLast, onNext }: AnswerFeedbackProps) => {
	const actionsRef = useRef<HTMLDivElement>(null);
	const latestOnNext = useLatest(onNext);

	// Move focus to the one control that matters now, so Tab and screen readers land on it.
	useEffect(() => {
		actionsRef.current?.querySelector('button')?.focus();
	}, []);

	// `event.repeat`: a held Enter from the typed submit must not skip straight through. The
	// Next button itself is handled here too, so its native click does not fire a second time.
	useEffect(() => {
		const onKeyDown = (event: KeyboardEvent) => {
			if (event.key !== 'Enter' || event.repeat || event.defaultPrevented) return;

			const nextButton = actionsRef.current?.querySelector('button') ?? null;
			const target = event.target;
			if (target instanceof Element && target !== nextButton && target.closest(CONTROL_SELECTOR)) return;

			event.preventDefault();
			latestOnNext.current();
		};

		window.addEventListener('keydown', onKeyDown);
		return () => window.removeEventListener('keydown', onKeyDown);
	}, [latestOnNext]);

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
