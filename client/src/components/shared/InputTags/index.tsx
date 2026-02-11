import React from 'react';
import classNames from 'classnames';
import { Chip } from '@/components/shared/Chip';
import styles from './InputTags.module.scss';

export interface InputTagsProps {
	defaultTags?: string[];
	onChange?: (nextTags: string[]) => void;
	placeholder?: string;
	disabled?: boolean;
	maxTags?: number;
	allowDuplicates?: boolean;
	label?: string;
	id?: string;
	name?: string;
	className?: string;
	inputClassName?: string;
	'aria-label'?: string;
	'aria-labelledby'?: string;
	hideLabel?: boolean;
}

// TODO: are these key values compatible with most current browsers
const isDelimiterKey = (event: React.KeyboardEvent<HTMLInputElement>): boolean => {
	return event.key === 'Enter' || event.key === ',' || event.key === ' ' || event.key === 'Spacebar';
};

export const InputTags: React.FunctionComponent<InputTagsProps> = ({
	defaultTags = [],
	onChange,
	placeholder,
	disabled = false,
	maxTags,
	allowDuplicates = false,
	label,
	id,
	name,
	className,
	inputClassName,
	'aria-label': ariaLabel,
	'aria-labelledby': ariaLabelledby,
	hideLabel,
}) => {
	const isControlled = !!defaultTags?.length;
	const [tags, setInternalTags] = React.useState<string[]>(isControlled ? defaultTags : []);
	const [inputValue, setInputValue] = React.useState<string>('');

	const autoId = React.useId();
	const inputId = id ?? `input-tags-${autoId}`;

	const updateTags = React.useCallback(
		(nextTags: string[]) => {
			setInternalTags(nextTags);

			if (isControlled) {
				onChange?.(nextTags);
			}
		},
		[isControlled, onChange],
	);

	const canAddTag = React.useCallback(
		(candidate: string) => {
			if (!candidate) {
				return false;
			}

			if (maxTags !== undefined && tags.length >= maxTags) {
				return false;
			}

			if (!allowDuplicates) {
				const normalizedCandidate = candidate.toLowerCase();
				const hasDuplicate = tags.some((tag) => tag.toLowerCase() === normalizedCandidate);
				if (hasDuplicate) {
					return false;
				}
			}

			return true;
		},
		[allowDuplicates, maxTags, tags],
	);

	const addTag = React.useCallback(
		(rawTag: string) => {
			const normalizedTag = rawTag.trim();

			if (!canAddTag(normalizedTag)) {
				return false;
			}

			updateTags([...tags, normalizedTag]);
			return true;
		},
		[canAddTag, tags, updateTags],
	);

	const handleInputChange = (event: React.ChangeEvent<HTMLInputElement>) => {
		setInputValue(event.target.value);
	};

	const handleKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
		if (disabled || event.nativeEvent.isComposing) {
			return;
		}

		if (isDelimiterKey(event)) {
			event.preventDefault();
			const didAdd = addTag(inputValue);
			if (didAdd) {
				setInputValue('');
			}
			return;
		}

		if (event.key === 'Backspace' && inputValue.length === 0 && tags.length > 0) {
			event.preventDefault();
			updateTags(tags.slice(0, -1));
		}
	};

	const handleRemove = (index: number) => {
		if (disabled) {
			return;
		}
		updateTags(tags.filter((_, tagIndex) => tagIndex !== index));
	};

	return (
		<div className={classNames(styles.wrapper, className)}>
			{label && !hideLabel && (
				<label className={styles.label} htmlFor={inputId}>
					{label}
				</label>
			)}
			<div
				className={classNames(styles.inputContainer, { [styles.disabled]: disabled })}
				data-disabled={disabled || undefined}
			>
				{tags.map((tag, index) => (
					<Chip
						disabled={disabled}
						key={`${tag}-${index}`}
						onCancel={() => handleRemove(index)}
						title={`Remove ${tag}`}
					>
						{tag}
					</Chip>
				))}
				<input
					id={inputId}
					type="text"
					name={name}
					className={classNames(styles.input, inputClassName)}
					value={inputValue}
					onChange={handleInputChange}
					onKeyDown={handleKeyDown}
					placeholder={placeholder}
					disabled={disabled}
					aria-label={label ? undefined : ariaLabel}
					aria-labelledby={ariaLabelledby}
				/>
			</div>
		</div>
	);
};
