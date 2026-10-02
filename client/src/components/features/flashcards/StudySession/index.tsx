import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import type { StudyCard, StudyDeck } from '@/api/flashcards/deck';
import { grade } from '@/api/flashcards/grading';
import type { SaveStatus } from '@/api/flashcards/sessions';
import { Stack } from '@/components/shared/layout';
import type { SavedListType } from '@/shared/constants/enums';
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
	/** Fires for every answer, with the round it belongs to (1 = first pass). */
	onAnswer?: (answer: SessionAnswer, attemptNo: number) => void;
	/** Fires once per round, when its last card has been answered. */
	onRoundComplete?: (answers: SessionAnswer[], attemptNo: number) => void;
	/** Where the recorder got to; `disabled` for a visitor. */
	saveStatus?: SaveStatus;
	/** The custom catalogue type missed items can be saved into from the summary. */
	bookmarkCatalogueType?: SavedListType | null;
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
 * One run through a deck: question → feedback → next card → summary, then optionally a
 * "retry missed" round over the cards that were wrong (attempt number + 1, same options).
 * Grading happens here, in the client (epic #413): options compare the picked text with
 * the card's display answer, typed answers go through `grade()`. Options arrive shuffled
 * by the deck seed, so nothing here is random.
 */
export const StudySession = ({
	deck,
	catalogueHref,
	onChangeSetup,
	onAnswer,
	onRoundComplete,
	saveStatus = 'disabled',
	bookmarkCatalogueType = null,
}: StudySessionProps) => {
	const [cards, setCards] = useState<StudyCard[]>(deck.cards);
	const [attemptNo, setAttemptNo] = useState(1);
	const [phase, setPhase] = useState<Phase>({ kind: 'question', index: 0 });
	const [answers, setAnswers] = useState<SessionAnswer[]>([]);
	const startedAtRef = useRef<number>(Date.now());
	const reportedRoundRef = useRef<number | null>(null);

	const promptJapanese = deck.config.prompt !== 'meaning';
	const answerJapanese = deck.config.answer !== 'meaning';
	const total = cards.length;
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
			onAnswer?.(answer, attemptNo);
		},
		[attemptNo, onAnswer],
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

	const startRound = useCallback((roundCards: StudyCard[], roundNo: number) => {
		setCards(roundCards);
		setAttemptNo(roundNo);
		setAnswers([]);
		startedAtRef.current = Date.now();
		setPhase({ kind: 'question', index: 0 });
	}, []);

	const restart = useCallback(() => startRound(deck.cards, 1), [deck.cards, startRound]);

	const retryMissed = useCallback(() => {
		const missed = answers.filter((answer) => !answer.correct).map((answer) => answer.card);
		if (missed.length > 0) {
			startRound(missed, attemptNo + 1);
		}
	}, [answers, attemptNo, startRound]);

	useEffect(() => {
		if (phase.kind === 'summary' && reportedRoundRef.current !== attemptNo) {
			reportedRoundRef.current = attemptNo;
			onRoundComplete?.(answers, attemptNo);
		}
		if (phase.kind !== 'summary' && reportedRoundRef.current === attemptNo) {
			// A new round with the same number only happens on restart; let it report again.
			reportedRoundRef.current = null;
		}
	}, [phase.kind, answers, attemptNo, onRoundComplete]);

	if (phase.kind === 'summary') {
		return (
			<SessionSummary
				answers={answers}
				attemptNo={attemptNo}
				promptJapanese={promptJapanese}
				answerJapanese={answerJapanese}
				catalogueTitle={deck.catalogue.title}
				catalogueHref={catalogueHref}
				saveStatus={saveStatus}
				bookmarkCatalogueType={bookmarkCatalogueType}
				onRetryMissed={retryMissed}
				onRestart={restart}
				onChangeSetup={onChangeSetup}
			/>
		);
	}

	const card = cards[phase.index];
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
						cardKey={`${attemptNo}-${card.itemId}`}
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
