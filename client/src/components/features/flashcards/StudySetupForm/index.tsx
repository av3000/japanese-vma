import { useEffect, useId, useMemo } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import {
	STUDY_MAX_COUNT,
	STUDY_MIN_COUNT,
	isTypeableField,
	isValidStudyCombination,
	studyFamilyFor,
	studyFieldsFor,
	type StudyConfig,
} from '@/api/flashcards/deck';
import { AnswerMode } from '@/api/generated/model/answerMode';
import { FlashcardField } from '@/api/generated/model/flashcardField';
import { ScriptStrictness } from '@/api/generated/model/scriptStrictness';
import { Alert } from '@/components/shared/Alert';
import { Button } from '@/components/shared/Button';
import { Checkbox, Field, FieldMessage, Input, Label, Select } from '@/components/shared/FormControls';
import { Stack } from '@/components/shared/layout';
import { useLatest } from '@/hooks/useLatest';
import { flashcardFieldLabel } from '../fieldLabels';
import styles from './StudySetupForm.module.css';
import { buildStudySetupSchema, type StudySetupValues } from './studySetupSchema';

/** What the route knows about the deck for the current configuration. */
export type StudyDeckStatus =
	| { kind: 'loading' }
	| { kind: 'ready'; totalItems: number; eligibleItems: number; excludedEmptyAnswerField: number }
	| { kind: 'error'; title: string; detail: string | null };

export interface StudySetupFormProps {
	catalogueType: number;
	value: StudyConfig;
	/** Fires on every valid change so the route can keep the URL and the deck preview current. */
	onChange: (config: StudyConfig) => void;
	onStart: (config: StudyConfig) => void;
	deckStatus: StudyDeckStatus;
}

const SCRIPT_FIELDS: readonly FlashcardField[] = [FlashcardField.onyomi, FlashcardField.kunyomi];

const toValues = (config: StudyConfig): StudySetupValues => ({
	prompt: config.prompt,
	answer: config.answer,
	mode: config.mode,
	lenient: config.script === ScriptStrictness.lenient,
	count: config.count,
});

const toConfig = (values: StudySetupValues, seed: number | undefined): StudyConfig => ({
	prompt: values.prompt,
	answer: values.answer,
	mode: values.mode,
	script: values.lenient ? ScriptStrictness.lenient : ScriptStrictness.strict,
	count: values.count,
	...(seed !== undefined ? { seed } : {}),
});

const sameConfig = (a: StudyConfig, b: StudyConfig): boolean =>
	a.prompt === b.prompt && a.answer === b.answer && a.mode === b.mode && a.script === b.script && a.count === b.count;

/**
 * Chooses what a card shows and how it is answered. Controlled by the route: `value` comes
 * from the URL, every valid change goes back through `onChange`, and Start hands the final
 * config over. The deck preview (counts, skipped items, backend refusals) is rendered from
 * `deckStatus` so the form itself makes no requests.
 *
 * The answer and mode are repaired in the prompt's and answer's change handlers (a prompt
 * change can leave them invalid for this type); the URL write is a `watch` subscription.
 * Neither is a derived-state effect, so a change settles in one pass.
 */
export const StudySetupForm = ({ catalogueType, value, onChange, onStart, deckStatus }: StudySetupFormProps) => {
	const family = studyFamilyFor(catalogueType);
	const fields = studyFieldsFor(catalogueType);
	const schema = useMemo(() => buildStudySetupSchema(catalogueType), [catalogueType]);
	const id = useId();
	const latestValue = useLatest(value);
	const latestOnChange = useLatest(onChange);
	const {
		register,
		handleSubmit,
		watch,
		setValue,
		getValues,
		reset,
		formState: { errors, isValid },
	} = useForm<StudySetupValues>({
		defaultValues: toValues(value),
		mode: 'onChange',
		resolver: zodResolver(schema),
	});

	const prompt = watch('prompt');
	const answer = watch('answer');
	const mode = watch('mode');

	const answersFor = (forPrompt: FlashcardField): FlashcardField[] =>
		fields.filter((field) =>
			isValidStudyCombination(catalogueType, forPrompt, field, AnswerMode.options),
		) as FlashcardField[];

	// The URL is the source of truth. When it changes from outside (back button, the route
	// pinning a seed), follow it; a URL change caused by this form's own write is a no-op.
	useEffect(() => {
		if (!sameConfig(toConfig(getValues(), value.seed), value)) {
			reset(toValues(value));
		}
	}, [value, getValues, reset]);

	// Every valid change goes to the URL. Validity is checked against the schema directly,
	// so this never depends on formState catching up.
	useEffect(() => {
		const subscription = watch((raw) => {
			const parsed = schema.safeParse(raw);
			if (!parsed.success) return;

			const next = toConfig(parsed.data, latestValue.current.seed);
			if (!sameConfig(next, latestValue.current)) {
				latestOnChange.current(next);
			}
		});

		return () => subscription.unsubscribe();
	}, [watch, schema, latestValue, latestOnChange]);

	if (!family) {
		return (
			<Alert tone="warning" heading="This catalogue cannot be studied">
				Flashcards are available for kanji, words and radicals catalogues.
			</Alert>
		);
	}

	const promptField = register('prompt', {
		onChange: (event: { target: { value: string } }) => {
			const nextPrompt = event.target.value as FlashcardField;
			const answers = answersFor(nextPrompt);
			if (!answers.includes(getValues('answer'))) {
				setValue('answer', answers[0] ?? FlashcardField.meaning, { shouldValidate: true });
			}
			if (getValues('mode') === AnswerMode.typed && !isTypeableField(getValues('answer'))) {
				setValue('mode', AnswerMode.options, { shouldValidate: true });
			}
		},
	});

	const answerField = register('answer', {
		onChange: (event: { target: { value: string } }) => {
			if (getValues('mode') === AnswerMode.typed && !isTypeableField(event.target.value as FlashcardField)) {
				setValue('mode', AnswerMode.options, { shouldValidate: true });
			}
		},
	});

	const answerOptions = answersFor(prompt);
	const typedAllowed = isTypeableField(answer);
	const showScript = SCRIPT_FIELDS.includes(answer);

	return (
		<Stack
			as="form"
			gap="md"
			className={styles.form}
			onSubmit={handleSubmit((values) => onStart(toConfig(values, value.seed)))}
			aria-labelledby={`${id}-legend`}
		>
			<h2 id={`${id}-legend`} className={styles.legend}>
				Set up your cards
			</h2>

			<div className={styles.grid}>
				<Field>
					<Label htmlFor={`${id}-prompt`}>Card shows</Label>
					<Select id={`${id}-prompt`} {...promptField}>
						{fields.map((field) => (
							<option key={field} value={field}>
								{flashcardFieldLabel(field, family)}
							</option>
						))}
					</Select>
				</Field>

				<Field>
					<Label htmlFor={`${id}-answer`}>You answer with</Label>
					<Select id={`${id}-answer`} isInvalid={Boolean(errors.answer)} {...answerField}>
						{answerOptions.map((field) => (
							<option key={field} value={field}>
								{flashcardFieldLabel(field, family)}
							</option>
						))}
					</Select>
					<FieldMessage tone="error">{errors.answer?.message}</FieldMessage>
				</Field>

				<Field>
					<Label htmlFor={`${id}-mode`}>How</Label>
					<Select id={`${id}-mode`} {...register('mode')}>
						<option value={AnswerMode.options}>Pick one of four</option>
						{typedAllowed && <option value={AnswerMode.typed}>Type the answer</option>}
					</Select>
					{!typedAllowed && (
						<FieldMessage>
							A {flashcardFieldLabel(FlashcardField.character, family).toLowerCase()} answer is always
							picked from options.
						</FieldMessage>
					)}
				</Field>

				<Field>
					<Label htmlFor={`${id}-count`}>Cards per run</Label>
					<Input
						id={`${id}-count`}
						type="number"
						inputMode="numeric"
						min={STUDY_MIN_COUNT}
						max={STUDY_MAX_COUNT}
						isInvalid={Boolean(errors.count)}
						{...register('count', { valueAsNumber: true })}
					/>
					<FieldMessage tone="error">{errors.count?.message}</FieldMessage>
				</Field>
			</div>

			{showScript && mode === AnswerMode.typed && (
				<Checkbox
					label={
						answer === FlashcardField.onyomi
							? 'Accept hiragana too (on’yomi is written in katakana)'
							: 'Accept katakana too (kun’yomi is written in hiragana)'
					}
					{...register('lenient')}
				/>
			)}

			<DeckPreview status={deckStatus} />

			<div>
				<Button type="submit" variant="primary" disabled={!isValid || deckStatus.kind !== 'ready'}>
					Start studying
				</Button>
			</div>
		</Stack>
	);
};

const DeckPreview = ({ status }: { status: StudyDeckStatus }) => {
	if (status.kind === 'loading') {
		return (
			<p className={styles.preview} aria-live="polite">
				Counting cards…
			</p>
		);
	}

	if (status.kind === 'error') {
		return (
			<Alert tone="warning" heading={status.title}>
				{status.detail ?? 'Try another combination.'}
			</Alert>
		);
	}

	const skipped = status.excludedEmptyAnswerField;

	return (
		<p className={styles.preview} aria-live="polite">
			<strong>{status.eligibleItems}</strong> of {status.totalItems} items can be asked this way
			{skipped > 0 && (
				<>
					{' '}
					({skipped} {skipped === 1 ? 'has' : 'have'} nothing to answer with and will be skipped)
				</>
			)}
			.
		</p>
	);
};

export default StudySetupForm;
