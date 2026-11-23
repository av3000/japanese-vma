import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, within } from 'storybook/test';
import { KeywordFilters } from './KeywordFilters';

const meta = {
	title: 'Features/Japanese/KeywordFilters',
	component: KeywordFilters,
	parameters: { layout: 'padded' },
	args: {
		label: 'Word filters',
		searchLabel: 'Search words by keyword',
		placeholder: 'Word, reading or meaning',
		defaultKeyword: '',
		onSearch: fn(),
	},
} satisfies Meta<typeof KeywordFilters>;

export default meta;

type Story = StoryObj<typeof meta>;

/**
 * Nothing applied. The component has no debounce: typing only changes the draft, and the keyword
 * is applied once, trimmed, on Enter or the Search button.
 */
export const Default: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		const input = canvas.getByRole('searchbox', { name: 'Search words by keyword' });

		await userEvent.type(input, '  water ');
		await expect(args.onSearch).not.toHaveBeenCalled();

		await userEvent.type(input, '{enter}');
		await expect(args.onSearch).toHaveBeenCalledTimes(1);
		await expect(args.onSearch).toHaveBeenCalledWith('water');
	},
};

/** The applied keyword from the URL seeds the draft; the Search button applies an edit. */
export const Filled: Story = {
	args: { defaultKeyword: '水' },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		const input = canvas.getByRole('searchbox', { name: 'Search words by keyword' });

		await expect(input).toHaveValue('水');

		await userEvent.type(input, '道');
		await userEvent.click(canvas.getByRole('button', { name: 'Search' }));
		await expect(args.onSearch).toHaveBeenCalledWith('水道');
	},
};

/** Clearing the draft and searching applies the empty keyword, which resets the list. */
export const ClearAndSearch: Story = {
	args: { defaultKeyword: '水' },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await userEvent.clear(canvas.getByRole('searchbox', { name: 'Search words by keyword' }));
		await userEvent.click(canvas.getByRole('button', { name: 'Search' }));
		await expect(args.onSearch).toHaveBeenCalledWith('');
	},
};

export const Mobile: Story = {
	args: { defaultKeyword: '水' },
	globals: { viewport: { value: 'xs' } },
};
