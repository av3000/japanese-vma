import { useState } from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, fn, userEvent, within } from '@storybook/test';
import { Button } from '@/components/shared/Button';
import { Cluster } from '@/components/shared/layout';
import { FilterBar } from './';

const SORT_OPTIONS = [
	{ value: '-created_at', label: 'Newest first' },
	{ value: 'created_at', label: 'Oldest first' },
	{ value: 'title_jp', label: 'Title' },
] as const;

const JLPT_OPTIONS = [
	{ value: '', label: 'All levels' },
	{ value: '5', label: 'N5' },
	{ value: '4', label: 'N4' },
	{ value: '3', label: 'N3' },
	{ value: '2', label: 'N2' },
	{ value: '1', label: 'N1' },
	{ value: '-', label: 'Uncommon' },
] as const;

const HASHTAGS = ['grammar', 'news', 'kyoto'];

const meta = {
	title: 'Shared/FilterBar',
	component: FilterBar,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: { onSubmit: fn(), label: 'Article filters' },
} satisfies Meta<typeof FilterBar>;

export default meta;

type Story = StoryObj<typeof meta>;

type ExampleProps = {
	onSubmit: () => void;
	initialSearch?: string;
	withFilters?: boolean;
	hasActiveFilters?: boolean;
};

/** The page owns this state in real use; a story needs a little of its own to type into. */
const Example = ({ onSubmit, initialSearch = '', withFilters = false, hasActiveFilters = false }: ExampleProps) => {
	const [search, setSearch] = useState(initialSearch);
	const [sort, setSort] = useState<(typeof SORT_OPTIONS)[number]['value']>('-created_at');
	const [jlpt, setJlpt] = useState<(typeof JLPT_OPTIONS)[number]['value']>('');
	const [pressed, setPressed] = useState<string[]>([]);

	const toggleHashtag = (tag: string) =>
		setPressed((current) => (current.includes(tag) ? current.filter((item) => item !== tag) : [...current, tag]));

	return (
		<FilterBar onSubmit={onSubmit} label="Article filters">
			<FilterBar.Search
				label="Search articles"
				placeholder="Search article titles"
				value={search}
				onChange={setSearch}
				hint={search.trim().length === 1 ? 'Enter at least 2 characters.' : undefined}
			/>
			{withFilters && (
				<>
					<FilterBar.Filters>
						<FilterBar.Select label="JLPT level" value={jlpt} options={JLPT_OPTIONS} onChange={setJlpt} />
					</FilterBar.Filters>
					<FilterBar.Sort label="Sort articles" value={sort} options={SORT_OPTIONS} onChange={setSort} />
				</>
			)}
			<FilterBar.Reset
				active={hasActiveFilters}
				onClick={() => {
					setSearch('');
					setJlpt('');
				}}
			/>
			{withFilters && (
				<FilterBar.Filters fullWidth>
					<fieldset>
						<legend>Hashtags</legend>
						<Cluster gap="xs">
							{HASHTAGS.map((tag) => (
								<Button
									key={tag}
									type="button"
									variant={pressed.includes(tag) ? 'primary' : 'secondary-outline'}
									aria-pressed={pressed.includes(tag)}
									onClick={() => toggleHashtag(tag)}
								>
									#{tag}
								</Button>
							))}
						</Cluster>
					</fieldset>
				</FilterBar.Filters>
			)}
		</FilterBar>
	);
};

/** Search only. Enter in the input and the button both submit. */
export const Default: Story = {
	render: (args) => <Example onSubmit={args.onSubmit} />,
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('search', { name: 'Article filters' })).toBeVisible();
		await userEvent.type(canvas.getByRole('searchbox', { name: 'Search articles' }), '文法{Enter}');
		await expect(args.onSubmit).toHaveBeenCalledTimes(1);

		await userEvent.click(canvas.getByRole('button', { name: 'Search' }));
		await expect(args.onSubmit).toHaveBeenCalledTimes(2);
		await expect(canvas.queryByRole('button', { name: 'Reset' })).not.toBeInTheDocument();
	},
};

export const WithFiltersAndSort: Story = {
	render: (args) => <Example onSubmit={args.onSubmit} withFilters />,
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await userEvent.selectOptions(canvas.getByRole('combobox', { name: 'JLPT level' }), 'N3');
		await expect(canvas.getByRole('combobox', { name: 'JLPT level' })).toHaveDisplayValue('N3');

		await userEvent.selectOptions(canvas.getByRole('combobox', { name: 'Sort articles' }), 'Title');
		await expect(canvas.getByRole('combobox', { name: 'Sort articles' })).toHaveDisplayValue('Title');

		const hashtag = canvas.getByRole('button', { name: '#grammar' });
		await userEvent.click(hashtag);
		await expect(hashtag).toHaveAttribute('aria-pressed', 'true');
	},
};

/** Reset appears only while something is applied, and clears the search when used. */
export const Active: Story = {
	render: (args) => <Example onSubmit={args.onSubmit} initialSearch="文法" withFilters hasActiveFilters />,
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('searchbox', { name: 'Search articles' })).toHaveValue('文法');
		await userEvent.click(canvas.getByRole('button', { name: 'Reset' }));
		await expect(canvas.getByRole('searchbox', { name: 'Search articles' })).toHaveValue('');
	},
};

/** A one-character search shows a hint, tied to the input for assistive technology. */
export const WithHint: Story = {
	render: (args) => <Example onSubmit={args.onSubmit} initialSearch="a" />,
	play: async ({ canvasElement }) => {
		await expect(
			within(canvasElement).getByRole('searchbox', { name: 'Search articles' }),
		).toHaveAccessibleDescription('Enter at least 2 characters.');
	},
};

/** Below 768px every control takes the full width and stacks. */
export const MobileViewport: Story = {
	render: (args) => <Example onSubmit={args.onSubmit} initialSearch="文法" withFilters hasActiveFilters />,
	parameters: { viewport: { defaultViewport: 'mobile1' } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('searchbox', { name: 'Search articles' })).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Reset' })).toBeVisible();
	},
};
