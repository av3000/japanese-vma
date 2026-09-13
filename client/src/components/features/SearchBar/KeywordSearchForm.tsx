import React, { FormEvent } from 'react';
import { Button } from '@/components/shared/Button';
import { Field, Input, InputGroup, Label } from '@/components/shared/FormControls';
import { Icon } from '@/components/shared/Icon';
import styles from './KeywordSearchForm.module.css';

export interface KeywordSearchFormProps {
	/** DOM id for the input; the label points at it. */
	id: string;
	label: string;
	value: string;
	placeholder?: string;
	onChange: (value: string) => void;
	onSubmit: () => void;
}

/**
 * Single keyword input with an attached submit button, centred on the page.
 * Shared by the Word and Sentence list routes.
 */
export const KeywordSearchForm: React.FC<KeywordSearchFormProps> = ({
	id,
	label,
	value,
	placeholder = 'Search',
	onChange,
	onSubmit,
}) => {
	const handleSubmit = (event: FormEvent<HTMLFormElement>) => {
		event.preventDefault();
		onSubmit();
	};

	return (
		<form onSubmit={handleSubmit} className={styles.form} role="search">
			<Field>
				<Label htmlFor={id}>{label}</Label>
				<InputGroup>
					<Input
						id={id}
						type="text"
						name="keyword"
						size="sm"
						placeholder={placeholder}
						value={value}
						onChange={(event) => onChange(event.target.value)}
					/>
					<Button type="submit" variant="outline" size="sm">
						<Icon name="searchSolid" size="sm" />
						<span className={styles.submitLabel}>Search</span>
					</Button>
				</InputGroup>
			</Field>
		</form>
	);
};
