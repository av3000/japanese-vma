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
	/** Control id. Defaults to a generated one. */
	id?: string;
	className?: string;
	children: (control: FormFieldControlProps) => React.ReactNode;
}

const toMessages = (error: FormFieldProps['error']): string[] =>
	(Array.isArray(error) ? error : [error]).filter((message): message is string => Boolean(message));

/**
 * Label, control, hint and error with the ids wired up: the label points at the control, and the
 * control's `aria-describedby` lists the hint and then the error, so assistive technology reads why
 * a field is invalid. The control is a render prop so `react-hook-form`'s `register()` spreads
 * alongside the wiring.
 */
export const FormField: React.FC<FormFieldProps> = ({ label, hint, error, id, className, children }) => {
	const generatedId = React.useId();
	const controlId = id ?? generatedId;
	const hintId = `${controlId}-hint`;
	const errorId = `${controlId}-error`;
	const messages = toMessages(error);
	const hasHint = hint !== undefined && hint !== null && hint !== false && hint !== '';
	const isInvalid = messages.length > 0;
	const describedBy = [hasHint && hintId, isInvalid && errorId].filter(Boolean).join(' ') || undefined;

	return (
		<Field className={className}>
			<Label htmlFor={controlId}>{label}</Label>
			{children({ id: controlId, 'aria-describedby': describedBy, isInvalid })}
			{hasHint ? <FieldMessage id={hintId}>{hint}</FieldMessage> : null}
			{isInvalid ? (
				<div id={errorId}>
					{messages.map((message) => (
						<FieldMessage key={message} tone="error">
							{message}
						</FieldMessage>
					))}
				</div>
			) : null}
		</Field>
	);
};

/* InputGroup ------------------------------------------------------------- */

export type InputGroupProps = React.HTMLAttributes<HTMLDivElement>;

/** A control with an attached addon such as a Button. Replaces `.input-group`. */
export const InputGroup: React.FC<InputGroupProps> = ({ className, ...rest }) => (
	<div className={classNames(styles.group, className)} {...rest} />
);
