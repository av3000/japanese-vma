import React from 'react';
import classNames from 'classnames';
import { Chip } from '@/components/shared/Chip';
import styles from './InputTags.module.scss';

export interface InputTagsProps {
	value?: string[];
	defaultValue?: string[];
	onChange?: (nextTags: string[]) => void;
	placeholder?: string;
	disabled?: boolean;
	maxTags?: number;
	allowDuplicates?: boolean;
	normalizeTag?: (raw: string) => string;
	label?: string;
	id?: string;
	name?: string;
	className?: string;
	inputClassName?: string;
	'aria-label'?: string;
	'aria-labelledby'?: string;
}

// TODO: are these key values compatible with most current browsers
const isDelimiterKey = (event: React.KeyboardEvent<HTMLInputElement>): boolean => {
	return event.key === 'Enter' || event.key === ',' || event.key === ' ' || event.key === 'Spacebar';
};

export const InputTags: React.FunctionComponent<InputTagsProps> = ({
	value,
	defaultValue = [],
	onChange,
	placeholder,
	disabled = false,
	maxTags,
	allowDuplicates = false,
	normalizeTag,
	label,
	id,
	name,
	className,
	inputClassName,
	'aria-label': ariaLabel,
	'aria-labelledby': ariaLabelledby,
}) => {
	const [internalTags, setInternalTags] = React.useState<string[]>(defaultValue ?? []);
	const [inputValue, setInputValue] = React.useState<string>('');
	const isControlled = value !== undefined;
	const tags = isControlled ? value : internalTags;
	const autoId = React.useId();
	const inputId = id ?? `input-tags-${autoId}`;

	const normalize = React.useCallback(
		(rawValue: string) => {
			const trimmed = rawValue.trim();
			const normalized = normalizeTag ? normalizeTag(trimmed) : trimmed;
			return normalized.trim();
		},
		[normalizeTag],
	);

	const updateTags = React.useCallback(
		(nextTags: string[]) => {
			if (!isControlled) {
				setInternalTags(nextTags);
			}
			onChange?.(nextTags);
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
		(rawValue: string) => {
			const normalizedTag = normalize(rawValue);
			if (!canAddTag(normalizedTag)) {
				return false;
			}

			updateTags([...tags, normalizedTag]);
			return true;
		},
		[canAddTag, normalize, tags, updateTags],
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
			{label && (
				<label className={styles.label} htmlFor={inputId}>
					{label}
				</label>
			)}
			<div
				className={classNames('form-control', styles.inputContainer, { [styles.disabled]: disabled })}
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
