import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import KanjiFilters from './';

const meta = {
	title: 'Pages/Kanji list/KanjiFilters',
	component: KanjiFilters,
	parameters: { layout: 'padded' },
	args: { defaultKeyword: '', defaultJlpt: '', onSearch: fn() },
} satisfies Meta<typeof KanjiFilters>;

export default meta;

type Story = StoryObj<typeof meta>;

/** The keyword waits for Enter or the button, and is trimmed; the level is carried along unchanged. */
export const Default: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('search', { name: 'Kanji filters' })).toBeVisible();
		await expect(canvas.getByRole('combobox', { name: 'JLPT level' })).toHaveValue('');

		await userEvent.type(canvas.getByRole('searchbox', { name: 'Search kanji by keyword' }), '  水 ');
		await expect(args.onSearch).not.toHaveBeenCalled();

		await userEvent.click(canvas.getByRole('button', { name: 'Search' }));
		await waitFor(() => expect(args.onSearch).toHaveBeenCalledTimes(1));
		await expect(args.onSearch).toHaveBeenCalledWith({ keyword: '水', jlpt: '' });
	},
};

/** Enter in the search box submits too. */
export const SubmitsOnEnter: Story = {
	play: async ({ canvasElement, args }) => {
		await userEvent.type(
			within(canvasElement).getByRole('searchbox', { name: 'Search kanji by keyword' }),
			'みず{Enter}',
		);

		await waitFor(() => expect(args.onSearch).toHaveBeenCalledTimes(1));
		await expect(args.onSearch).toHaveBeenCalledWith({ keyword: 'みず', jlpt: '' });
	},
};

/** Both applied filters come from the URL and show in the controls. */
export const Applied: Story = {
	args: { defaultKeyword: '水', defaultJlpt: '3' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('searchbox', { name: 'Search kanji by keyword' })).toHaveValue('水');
		await expect(canvas.getByRole('combobox', { name: 'JLPT level' })).toHaveValue('3');
	},
};

/** Choosing a level applies at once, together with whatever keyword is typed. */
export const LevelAppliesAtOnce: Story = {
	args: { defaultKeyword: '海' },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await userEvent.selectOptions(canvas.getByRole('combobox', { name: 'JLPT level' }), 'N2');

		await expect(args.onSearch).toHaveBeenCalledTimes(1);
		await expect(args.onSearch).toHaveBeenCalledWith({ keyword: '海', jlpt: '2' });
	},
};

/** Kanji with no level are offered as "Uncommon" and sent as "-", the value the API uses. */
export const Uncommon: Story = {
	args: { defaultJlpt: '-' },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('combobox', { name: 'JLPT level' })).toHaveValue('-');

		await userEvent.selectOptions(canvas.getByRole('combobox', { name: 'JLPT level' }), 'All levels');
		await expect(args.onSearch).toHaveBeenCalledWith({ keyword: '', jlpt: '' });
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	args: { defaultKeyword: '水', defaultJlpt: '5' },
};
