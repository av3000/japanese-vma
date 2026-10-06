import * as React from 'react';
import classNames from 'classnames';
import styles from './FormControls.module.css';

export type ControlSize = 'sm' | 'md';

interface ControlModifiers {
	/** Visual size. Defaults to `md`. */
	size?: ControlSize;
	/** Marks the control invalid (red border, `aria-invalid`). */
	isInvalid?: boolean;
}

const controlClass = (
	size: ControlSize | undefined,
	isInvalid: boolean | undefined,
	...extra: Array<string | undefined | false>
) => classNames(styles.control, size === 'sm' && styles.controlSm, isInvalid && styles.controlInvalid, ...extra);

/* Field ------------------------------------------------------------------ */

export type FieldProps = React.HTMLAttributes<HTMLDivElement>;

/** Vertical wrapper for a label, control and message. Replaces `.form-group`. */
export const Field: React.FC<FieldProps> = ({ className, ...rest }) => (
	<div className={classNames(styles.field, className)} {...rest} />
);

/* Label ------------------------------------------------------------------ */

export type LabelProps = React.LabelHTMLAttributes<HTMLLabelElement>;

export const Label: React.FC<LabelProps> = ({ className, ...rest }) => (
	// The control is associated by the caller through `htmlFor`, which the rule cannot see through props.
	// eslint-disable-next-line jsx-a11y/label-has-associated-control
	<label className={classNames(styles.label, className)} {...rest} />
);

/* Input ------------------------------------------------------------------ */

export type InputProps = Omit<React.InputHTMLAttributes<HTMLInputElement>, 'size'> & ControlModifiers;

export const Input = React.forwardRef<HTMLInputElement, InputProps>(function Input(
	{ size, isInvalid, className, ...rest },
	ref,
) {
	return (
		<input
			ref={ref}
			className={controlClass(size, isInvalid, className)}
			aria-invalid={isInvalid || undefined}
			{...rest}
		/>
	);
});

/* Textarea --------------------------------------------------------------- */

export type TextareaProps = React.TextareaHTMLAttributes<HTMLTextAreaElement> &
	ControlModifiers & {
		/** Disable manual resizing. */
		noResize?: boolean;
	};

export const Textarea = React.forwardRef<HTMLTextAreaElement, TextareaProps>(function Textarea(
	{ size, isInvalid, noResize, className, ...rest },
	ref,
) {
	return (
		<textarea
			ref={ref}
			className={controlClass(size, isInvalid, styles.textarea, noResize && styles.textareaNoResize, className)}
			aria-invalid={isInvalid || undefined}
			{...rest}
		/>
	);
});

/* Select ----------------------------------------------------------------- */

export type SelectProps = Omit<React.SelectHTMLAttributes<HTMLSelectElement>, 'size'> & ControlModifiers;

export const Select = React.forwardRef<HTMLSelectElement, SelectProps>(function Select(
	{ size, isInvalid, className, children, ...rest },
	ref,
) {
	return (
		<select
			ref={ref}
			className={controlClass(size, isInvalid, styles.select, className)}
			aria-invalid={isInvalid || undefined}
			{...rest}
		>
			{children}
		</select>
	);
});

/* Checkbox --------------------------------------------------------------- */

export type CheckboxProps = Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type'> & {
	label: React.ReactNode;
	wrapperClassName?: string;
	/** Marks the checkbox invalid (`aria-invalid`, red label). */
	isInvalid?: boolean;
};

export const Checkbox = React.forwardRef<HTMLInputElement, CheckboxProps>(function Checkbox(
	{ label, wrapperClassName, isInvalid, ...rest },
	ref,
) {
	return (
		<label className={classNames(styles.checkbox, isInvalid && styles.checkboxInvalid, wrapperClassName)}>
			<input ref={ref} type="checkbox" aria-invalid={isInvalid || undefined} {...rest} />
			<span>{label}</span>
		</label>
	);
});

/* FieldMessage ----------------------------------------------------------- */

export interface FieldMessageProps extends React.HTMLAttributes<HTMLParagraphElement> {
	/** `hint` is muted helper text; `error` is red validation text. */
	tone?: 'hint' | 'error';
	/** Right-align (character counters). */
	alignEnd?: boolean;
}

/** Helper or validation text under a control. Replaces `.form-text`, `.text-muted`, `.text-danger`. */
export const FieldMessage: React.FC<FieldMessageProps> = ({
	tone = 'hint',
	alignEnd,
	className,
	children,
	...rest
}) => {
	if (children === undefined || children === null || children === false || children === '') return null;
	return (
		<p
			className={classNames(
				styles.message,
				tone === 'error' ? styles.messageError : styles.messageHint,
				alignEnd && styles.messageEnd,
				className,
			)}
			{...rest}
		>
			{children}
		</p>
	);
};

/* FormField -------------------------------------------------------------- */

/** Props a `FormField` hands to its control. Spread them onto `Input`, `Textarea` or `Select`. */
export interface FormFieldControlProps {
	id: string;
	'aria-describedby'?: string;
	isInvalid: boolean;
}

export interface FormFieldProps {
	label: React.ReactNode;
	/** Muted helper text under the control. Stays visible when an error shows. */
	hint?: React.ReactNode;
	/** One message or several; each renders on its own line. Empty values count as no error. */
	error?: string | string[];
	/**
	 * End-aligned on the hint row, e.g. `12 / 255`. Deliberately left out of `aria-describedby` and not
	 * live, so screen readers don't announce every keystroke; state the limit itself in `hint`.
	 */
	counter?: React.ReactNode;
	/** Control id. Defaults to a generated one. */
	id?: string;
	className?: string;
	children: (control: FormFieldControlProps) => React.ReactNode;
}

const isPresent = (node: React.ReactNode) => node !== undefined && node !== null && node !== false && node !== '';

const toMessages = (error: FormFieldProps['error']): string[] =>
	(Array.isArray(error) ? error : [error]).filter((message): message is string => Boolean(message));

/**
 * Label, control, hint and error with the ids wired up: the label points at the control, and the
 * control's `aria-describedby` lists the hint and then the error, so assistive technology reads why
 * a field is invalid. The control is a render prop so `react-hook-form`'s `register()` spreads
 * alongside the wiring.
 */
export const FormField: React.FC<FormFieldProps> = ({ label, hint, error, counter, id, className, children }) => {
	const generatedId = React.useId();
	const controlId = id ?? generatedId;
	const hintId = `${controlId}-hint`;
	const errorId = `${controlId}-error`;
	const messages = toMessages(error);
	const hasHint = isPresent(hint);
	const isInvalid = messages.length > 0;
	const describedBy = [hasHint && hintId, isInvalid && errorId].filter(Boolean).join(' ') || undefined;
	const hintMessage = hasHint ? <FieldMessage id={hintId}>{hint}</FieldMessage> : null;

	return (
		<Field className={className}>
			<Label htmlFor={controlId}>{label}</Label>
			{children({ id: controlId, 'aria-describedby': describedBy, isInvalid })}
			{isPresent(counter) ? (
				<div className={styles.messageRow}>
					{hintMessage}
					<FieldMessage alignEnd className={styles.counter}>
						{counter}
					</FieldMessage>
				</div>
			) : (
				hintMessage
			)}
			<FieldErrors id={errorId} messages={messages} />
		</Field>
	);
};

const FieldErrors: React.FC<{ id: string; messages: string[] }> = ({ id, messages }) =>
	messages.length > 0 ? (
		<div id={id}>
			{messages.map((message) => (
				<FieldMessage key={message} tone="error">
					{message}
				</FieldMessage>
			))}
		</div>
	) : null;

/* ChoiceGroup ------------------------------------------------------------ */

export interface ChoiceOption<T extends string | number> {
	value: T;
	label: string;
	/** One line under the label that spells out what the choice means. */
	description?: React.ReactNode;
	/** A Japanese character shown above the label. Decorative: the label carries the name. */
	glyph?: string;
}

export interface ChoiceGroupProps<T extends string | number> {
	legend: React.ReactNode;
	name: string;
	options: ReadonlyArray<ChoiceOption<T>>;
	value: T | undefined;
	onChange: (value: T) => void;
	onBlur?: () => void;
	/** Muted helper text under the choices; also the place to say why a group is disabled. */
	hint?: React.ReactNode;
	/** One message or several; each renders on its own line. */
	error?: string | string[];
	disabled?: boolean;
	/** Attached to the checked radio, or the first one when none is checked, so a form can focus the group. */
	inputRef?: React.Ref<HTMLInputElement>;
	/** Base for the generated ids. */
	id?: string;
	className?: string;
}

/**
 * A labelled group of native radios drawn as tiles. An error is announced through the fieldset's
 * description (radios do not support `aria-invalid`) and shown with a red border on every tile. Native inputs keep arrow-key movement, form
 * reset and screen-reader announcements without script. The checked tile shows the radio dot and a
 * heavier border and label, so the state never rests on colour alone.
 */
export function ChoiceGroup<T extends string | number>({
	legend,
	name,
	options,
	value,
	onChange,
	onBlur,
	hint,
	error,
	disabled,
	inputRef,
	id,
	className,
}: ChoiceGroupProps<T>) {
	const generatedId = React.useId();
	const baseId = id ?? generatedId;
	const hintId = `${baseId}-hint`;
	const errorId = `${baseId}-error`;
	const messages = toMessages(error);
	const hasHint = isPresent(hint);
	const isInvalid = messages.length > 0;
	const describedBy = [hasHint && hintId, isInvalid && errorId].filter(Boolean).join(' ') || undefined;
	const refIndex = Math.max(
		0,
		options.findIndex((option) => option.value === value),
	);

	return (
		<fieldset
			className={classNames(styles.choiceGroup, className)}
			aria-describedby={describedBy}
			disabled={disabled}
		>
			<legend className={styles.legend}>{legend}</legend>
			<div className={styles.choices}>
				{options.map((option, index) => {
					const optionId = `${baseId}-${index}`;
					const labelId = `${optionId}-label`;
					const descriptionId = `${optionId}-description`;
					const isChecked = option.value === value;

					return (
						<label
							key={String(option.value)}
							htmlFor={optionId}
							className={classNames(
								styles.choice,
								isChecked && styles.choiceChecked,
								isInvalid && styles.choiceInvalid,
							)}
						>
							<input
								ref={index === refIndex ? inputRef : undefined}
								id={optionId}
								type="radio"
								name={name}
								value={String(option.value)}
								checked={isChecked}
								onChange={() => onChange(option.value)}
								onBlur={onBlur}
								// The tile's text includes the description; name the radio by its label alone.
								aria-labelledby={labelId}
								aria-describedby={isPresent(option.description) ? descriptionId : undefined}
								className={styles.choiceInput}
							/>
							<span className={styles.choiceText}>
								{option.glyph ? (
									<span className={styles.choiceGlyph} aria-hidden="true">
										{option.glyph}
									</span>
								) : null}
								<span id={labelId} className={styles.choiceLabel}>
									{option.label}
								</span>
								{isPresent(option.description) ? (
									<span id={descriptionId} className={styles.choiceDescription}>
										{option.description}
									</span>
								) : null}
							</span>
						</label>
					);
				})}
			</div>
			{hasHint ? <FieldMessage id={hintId}>{hint}</FieldMessage> : null}
			<FieldErrors id={errorId} messages={messages} />
		</fieldset>
	);
}

/* InputGroup ------------------------------------------------------------- */

export type InputGroupProps = React.HTMLAttributes<HTMLDivElement>;

/** A control with an attached addon such as a Button. Replaces `.input-group`. */
export const InputGroup: React.FC<InputGroupProps> = ({ className, ...rest }) => (
	<div className={classNames(styles.group, className)} {...rest} />
);
