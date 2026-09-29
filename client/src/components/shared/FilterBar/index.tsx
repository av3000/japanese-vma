import * as React from 'react';
import classNames from 'classnames';
import { Button } from '@/components/shared/Button';
import { Field, FieldMessage, Input, InputGroup, Label, Select } from '@/components/shared/FormControls';
import { Icon } from '@/components/shared/Icon';
import styles from './FilterBar.module.css';

export interface FilterBarProps extends Omit<React.FormHTMLAttributes<HTMLFormElement>, 'onSubmit'> {
	/** Runs on Enter in the search input and on the search button. The default page reload is prevented. */
	onSubmit: () => void;
	/** Names the search landmark, for pages that show more than one. */
	label?: string;
}

/**
 * Layout and form semantics for a list's filters: a native `<form role="search">` that places its
 * slots in one responsive layout. It owns no state and never fetches; each page keeps its own
 * filter state and URL or query mapping and passes values and callbacks in.
 *
 * Slots render in the order given, usually Search, Filters, Sort, Reset.
 */
const FilterBarRoot: React.FC<FilterBarProps> = ({ onSubmit, label, className, children, ...rest }) => {
	const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
		event.preventDefault();
		onSubmit();
	};

	return (
		<form
			{...rest}
			role="search"
			aria-label={label}
			onSubmit={handleSubmit}
			className={classNames(styles.bar, className)}
		>
			{children}
		</form>
	);
};

/* Search ----------------------------------------------------------------- */

export interface FilterBarSearchProps {
	/** Visually hidden, but always present for assistive technology. */
	label: string;
	value: string;
	onChange: (value: string) => void;
	placeholder?: string;
	/** Helper text under the input, for example a minimum length. */
	hint?: React.ReactNode;
	/** Blocks the submit button, not the input. */
	submitDisabled?: boolean;
	submitLabel?: string;
	name?: string;
	id?: string;
}

const Search: React.FC<FilterBarSearchProps> = ({
	label,
	value,
	onChange,
	placeholder,
	hint,
	submitDisabled,
	submitLabel = 'Search',
	name,
	id,
}) => {
	const generatedId = React.useId();
	const inputId = id ?? generatedId;
	const hintId = `${inputId}-hint`;
	const hasHint = hint !== undefined && hint !== null && hint !== false && hint !== '';

	return (
		<Field className={styles.search}>
			<Label htmlFor={inputId} className={styles.visuallyHidden}>
				{label}
			</Label>
			<InputGroup>
				<Input
					id={inputId}
					type="search"
					name={name}
					placeholder={placeholder}
					value={value}
					aria-describedby={hasHint ? hintId : undefined}
					onChange={(event) => onChange(event.target.value)}
				/>
				<Button type="submit" variant="primary" disabled={submitDisabled}>
					<Icon name="searchSolid" size="sm" />
					{submitLabel}
				</Button>
			</InputGroup>
			<FieldMessage id={hintId} tone="hint">
				{hint}
			</FieldMessage>
		</Field>
	);
};

/* Filters ---------------------------------------------------------------- */

export interface FilterBarFiltersProps {
	children: React.ReactNode;
	/** Give the group its own row, for wide controls such as facet chips. */
	fullWidth?: boolean;
	className?: string;
}

/** Slot for page-specific controls: selects, facet chip groups, anything a page filters by. */
const Filters: React.FC<FilterBarFiltersProps> = ({ children, fullWidth, className }) => (
	<div className={classNames(styles.filters, fullWidth && styles.filtersFullWidth, className)}>{children}</div>
);

/* Select and Sort -------------------------------------------------------- */

export interface FilterBarOption<T extends string> {
	value: T;
	label: string;
}

export interface FilterBarSelectProps<T extends string> {
	/** Visually hidden, but always present for assistive technology. */
	label: string;
	value: T;
	options: ReadonlyArray<FilterBarOption<T>>;
	onChange: (value: T) => void;
	name?: string;
	id?: string;
}

/** A labelled select for a page's own filter, with the options the caller passes. */
function FilterSelect<T extends string>({ label, value, options, onChange, name, id }: FilterBarSelectProps<T>) {
	const generatedId = React.useId();
	const selectId = id ?? generatedId;

	return (
		<Field className={styles.select}>
			<Label htmlFor={selectId} className={styles.visuallyHidden}>
				{label}
			</Label>
			<Select id={selectId} name={name} value={value} onChange={(event) => onChange(event.target.value as T)}>
				{options.map((option) => (
					<option key={option.value} value={option.value}>
						{option.label}
					</option>
				))}
			</Select>
		</Field>
	);
}

export type FilterBarSortProps<T extends string> = Omit<FilterBarSelectProps<T>, 'label'> & {
	/** Defaults to "Sort by". */
	label?: string;
};

/** A labelled select for sort orders, for example `SORT_OPTIONS`. */
function Sort<T extends string>({ label = 'Sort by', ...rest }: FilterBarSortProps<T>) {
	return <FilterSelect label={label} {...rest} />;
}

/* Reset ------------------------------------------------------------------ */

export interface FilterBarResetProps {
	/** Rendered only while the caller says a filter is applied. */
	active: boolean;
	onClick: () => void;
	label?: string;
}

const Reset: React.FC<FilterBarResetProps> = ({ active, onClick, label = 'Reset' }) =>
	active ? (
		<Button type="button" variant="secondary-outline" onClick={onClick}>
			{label}
		</Button>
	) : null;

export const FilterBar = Object.assign(FilterBarRoot, { Search, Filters, Select: FilterSelect, Sort, Reset });

export default FilterBar;
