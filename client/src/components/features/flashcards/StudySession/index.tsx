import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { StudyCard, StudyDeck } from '@/api/flashcards/deck';
import { grade } from '@/api/flashcards/grading';
import { Stack } from '@/components/shared/layout';
import { AnswerFeedback } from '../AnswerFeedback';
import { AnswerInput } from '../AnswerInput';
import { AnswerOptions } from '../AnswerOptions';
import { FlashcardPrompt } from '../FlashcardPrompt';
import { SessionProgress } from '../SessionProgress';
import { SessionSummary, type SessionAnswer } from '../SessionSummary';
import styles from './StudySession.module.css';

export interface StudySessionProps {
	deck: StudyDeck;
	catalogueHref: string;
	onChangeSetup: () => void;
	/** Fires once, when the last card has been answered. */
	onComplete?: (answers: SessionAnswer[]) => void;
}

type Phase =
	| { kind: 'question'; index: number }
	| { kind: 'feedback'; index: number; answer: SessionAnswer }
	| { kind: 'summary' };

const ANSWER_LABEL: Record<StudyDeck['config']['answer'], string> = {
	character: 'Character',
	meaning: 'Meaning (English)',
	onyomi: 'On’yomi (katakana)',
	kunyomi: 'Kun’yomi (hiragana)',
	reading: 'Reading',
};

/**
 * One run through a deck: question → feedback → next card → summary. Grading happens here,
 * in the client (epic #413): options compare the picked text with the card's display
 * answer, typed answers go through `grade()`. Options arrive shuffled by the deck seed,
 * so nothing here is random.
 */
export const StudySession = ({ deck, catalogueHref, onChangeSetup, onComplete }: StudySessionProps) => {
	const [phase, setPhase] = useState<Phase>({ kind: 'question', index: 0 });
	const [answers, setAnswers] = useState<SessionAnswer[]>([]);
	const startedAtRef = useRef<number>(Date.now());
	const completedRef = useRef(false);

	const promptJapanese = deck.config.prompt !== 'meaning';
	const answerJapanese = deck.config.answer !== 'meaning';
	const total = deck.cards.length;
	const correctCount = useMemo(() => answers.filter((answer) => answer.correct).length, [answers]);

	const record = useCallback(
		(card: StudyCard, given: string, correct: boolean, matched: string | null, index: number) => {
			const answer: SessionAnswer = {
				card,
				given,
				correct,
				matched,
				responseMs: Date.now() - startedAtRef.current,
			};
			setAnswers((current) => [...current, answer]);
			setPhase({ kind: 'feedback', index, answer });
		},
		[],
	);

	const next = useCallback(() => {
		setPhase((current) => {
			if (current.kind !== 'feedback') return current;

			const nextIndex = current.index + 1;
			if (nextIndex >= total) {
				return { kind: 'summary' };
			}

			startedAtRef.current = Date.now();
			return { kind: 'question', index: nextIndex };
		});
	}, [total]);

	const restart = useCallback(() => {
		setAnswers([]);
		completedRef.current = false;
		startedAtRef.current = Date.now();
		setPhase({ kind: 'question', index: 0 });
	}, []);

	useEffect(() => {
		if (phase.kind === 'summary' && !completedRef.current) {
			completedRef.current = true;
			onComplete?.(answers);
		}
	}, [phase.kind, answers, onComplete]);

	if (phase.kind === 'summary') {
		return (
			<SessionSummary
				answers={answers}
				promptJapanese={promptJapanese}
				answerJapanese={answerJapanese}
				catalogueTitle={deck.catalogue.title}
				catalogueHref={catalogueHref}
				onRestart={restart}
				onChangeSetup={onChangeSetup}
			/>
		);
	}

	const card = deck.cards[phase.index];
	const isLast = phase.index === total - 1;

	return (
		<Stack as="section" gap="lg" aria-label="Study session" className={styles.session}>
			<SessionProgress current={phase.index + 1} total={total} correct={correctCount} />

			<FlashcardPrompt card={card} field={deck.config.prompt} />

			{phase.kind === 'question' ? (
				deck.config.mode === 'options' && card.options ? (
					<AnswerOptions
						options={card.options}
						japanese={answerJapanese}
						onSelect={(option) =>
							record(
								card,
								option,
								option === card.displayAnswer,
								option === card.displayAnswer ? option : null,
								phase.index,
							)
						}
					/>
				) : (
					<AnswerInput
						cardKey={card.itemId}
						label={ANSWER_LABEL[deck.config.answer]}
						japanese={answerJapanese}
						onSubmit={(given) => {
							const result = grade({
								answerField: deck.config.answer,
								script: deck.config.script,
								acceptedAnswers: card.acceptedAnswers,
								given,
							});
							record(card, given, result.correct, result.matched, phase.index);
						}}
					/>
				)
			) : (
				<AnswerFeedback
					card={card}
					correct={phase.answer.correct}
					matched={phase.answer.matched}
					given={phase.answer.given}
					japanese={answerJapanese}
					isLast={isLast}
					onNext={next}
				/>
			)}
		</Stack>
	);
};

export default StudySession;
