import { useState, type ComponentProps } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import { CatalogueFilters, DEFAULT_CATALOGUE_SEARCH_FILTERS } from './';

type FiltersProps = ComponentProps<typeof CatalogueFilters>;

/** Holds the value the way a page does, and still reports every change to the `onChange` spy. */
const OwnedByPage = ({ value, onChange, onSubmit }: FiltersProps) => {
	const [current, setCurrent] = useState(value);

	return (
		<CatalogueFilters
			value={current}
			onChange={(next) => {
				setCurrent(next);
				onChange(next);
			}}
			onSubmit={onSubmit}
		/>
	);
};

const meta = {
	title: 'Features/Catalogues/CatalogueFilters',
	component: CatalogueFilters,
	parameters: { layout: 'padded' },
	args: { value: DEFAULT_CATALOGUE_SEARCH_FILTERS, onChange: fn(), onSubmit: fn() },
	render: (args) => <OwnedByPage {...args} />,
} satisfies Meta<typeof CatalogueFilters>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Typing reports every change with the rest of the value untouched; only a submit asks for results. */
export const Default: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('search', { name: 'Catalogue filters' })).toBeVisible();
		await expect(canvas.getByRole('combobox', { name: 'Catalogue type' })).toHaveValue('');
		await expect(canvas.getByRole('combobox', { name: 'Sort by' })).toHaveValue('new');

		await userEvent.type(canvas.getByRole('searchbox', { name: 'Search catalogues' }), 'station');
		await expect(args.onChange).toHaveBeenLastCalledWith({ keyword: 'station', sortByWhat: 'new', filterType: '' });
		await expect(args.onSubmit).not.toHaveBeenCalled();

		await userEvent.click(canvas.getByRole('button', { name: 'Search' }));
		await waitFor(() => expect(args.onSubmit).toHaveBeenCalledTimes(1));
	},
};

export const ChangesTypeAndSort: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await userEvent.selectOptions(canvas.getByRole('combobox', { name: 'Catalogue type' }), 'Kanjis');
		await expect(args.onChange).toHaveBeenLastCalledWith({ keyword: '', sortByWhat: 'new', filterType: '6' });

		await userEvent.selectOptions(canvas.getByRole('combobox', { name: 'Sort by' }), 'Popular');
		await expect(args.onChange).toHaveBeenLastCalledWith({ keyword: '', sortByWhat: 'pop', filterType: '6' });
		await expect(args.onChange).toHaveBeenCalledTimes(2);
	},
};

export const Filled: Story = {
	args: { value: { keyword: '#trains', sortByWhat: 'pop', filterType: '7' } },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('searchbox', { name: 'Search catalogues' })).toHaveValue('#trains');
		await expect(canvas.getByRole('combobox', { name: 'Catalogue type' })).toHaveValue('7');
		await expect(canvas.getByRole('combobox', { name: 'Sort by' })).toHaveValue('pop');

		await userEvent.type(canvas.getByRole('searchbox', { name: 'Search catalogues' }), '{Enter}');
		await waitFor(() => expect(args.onSubmit).toHaveBeenCalledTimes(1));
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	args: { value: { keyword: '#trains', sortByWhat: 'pop', filterType: '7' } },
};
