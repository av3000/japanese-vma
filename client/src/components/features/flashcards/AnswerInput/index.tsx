import { useEffect, useId, useRef, useState, type FormEvent, type KeyboardEvent } from 'react';
import { Button } from '@/components/shared/Button';
import { Input, Label } from '@/components/shared/FormControls';
import { Cluster } from '@/components/shared/layout';
import styles from './AnswerInput.module.css';

export interface AnswerInputProps {
	/** Label text, e.g. "Kun’yomi (hiragana)". */
	label: string;
	/** Kana answers get `lang="ja"` so the IME and font follow. */
	japanese: boolean;
	onSubmit: (given: string) => void;
	disabled?: boolean;
	/** Reset key: a new card clears the field and refocuses it. */
	cardKey: string | number;
}

/**
 * The typed answer. Enter submits, except while a Japanese IME is composing: confirming a
 * kana conversion also fires Enter, and that keystroke must not grade the half-typed
 * answer. Empty input never submits.
 */
export const AnswerInput = ({ label, japanese, onSubmit, disabled = false, cardKey }: AnswerInputProps) => {
	const id = useId();
	const inputRef = useRef<HTMLInputElement>(null);
	const composingRef = useRef(false);
	const [value, setValue] = useState('');
	const trimmed = value.trim();

	useEffect(() => {
		setValue('');
		composingRef.current = false;
		inputRef.current?.focus();
	}, [cardKey]);

	const submit = (event: FormEvent<HTMLFormElement>) => {
		event.preventDefault();
		if (composingRef.current || trimmed === '' || disabled) return;
		onSubmit(trimmed);
	};

	const onKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
		if (event.key === 'Enter' && (composingRef.current || event.nativeEvent.isComposing)) {
			event.preventDefault();
		}
	};

	return (
		<form className={styles.form} onSubmit={submit}>
			<Label htmlFor={id}>{label}</Label>
			<Cluster gap="sm" align="stretch">
				<Input
					ref={inputRef}
					id={id}
					className={styles.input}
					value={value}
					lang={japanese ? 'ja' : undefined}
					autoComplete="off"
					autoCapitalize="none"
					spellCheck={false}
					disabled={disabled}
					onChange={(event) => setValue(event.target.value)}
					onKeyDown={onKeyDown}
					onCompositionStart={() => {
						composingRef.current = true;
					}}
					onCompositionEnd={() => {
						composingRef.current = false;
					}}
				/>
				<Button type="submit" variant="primary" disabled={disabled || trimmed === ''}>
					Check
				</Button>
			</Cluster>
		</form>
	);
};

export default AnswerInput;
