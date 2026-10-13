import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, within } from 'storybook/test';
import { PostSort } from '@/api/generated/model/postSort';
import PostFilters from './';

const meta = {
	title: 'Features/Community/PostFilters',
	component: PostFilters,
	parameters: { layout: 'padded' },
	args: {
		filters: { per_page: 10, sort: PostSort.newest },
		onChange: fn(),
		onReset: fn(),
	},
	argTypes: { onChange: { control: false }, onReset: { control: false } },
} satisfies Meta<typeof PostFilters>;

export default meta;

type Story = StoryObj<typeof meta>;

/** No filter applied: the search, topic and sort controls, and no Reset. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('search', { name: 'Post filters' })).toBeVisible();
		await expect(canvas.getByRole('searchbox', { name: 'Search posts' })).toHaveValue('');
		await expect(canvas.getByRole('combobox', { name: 'Filter by topic' })).toHaveDisplayValue('All topics');
		await expect(canvas.getByRole('combobox', { name: 'Sort posts' })).toHaveDisplayValue('Newest');
		await expect(canvas.queryByRole('button', { name: 'Reset' })).toBeNull();
	},
};

/** Filters from the URL: the controls show them, and Reset appears. */
export const Filled: Story = {
	args: { filters: { per_page: 10, keyword: '助詞', topic: 3, sort: PostSort.popular } },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('searchbox', { name: 'Search posts' })).toHaveValue('助詞');
		await expect(canvas.getByRole('combobox', { name: 'Filter by topic' })).toHaveDisplayValue('FAQ');
		await expect(canvas.getByRole('combobox', { name: 'Sort posts' })).toHaveDisplayValue('Most popular');

		await userEvent.click(canvas.getByRole('button', { name: 'Reset' }));
		await expect(args.onReset).toHaveBeenCalledTimes(1);
	},
};

/**
 * Typing only drafts the keyword; Enter submits it with every other filter kept. Topic and sort
 * apply as soon as they change.
 */
export const ChangesAndSubmits: Story = {
	args: { filters: { per_page: 10, topic: 3, sort: PostSort.newest } },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await userEvent.type(canvas.getByRole('searchbox', { name: 'Search posts' }), '助詞');
		await expect(args.onChange).not.toHaveBeenCalled();

		await userEvent.keyboard('{Enter}');
		await expect(args.onChange).toHaveBeenLastCalledWith({
			keyword: '助詞',
			hashtag: '',
			topic: '3',
			sort: 'newest',
		});

		await userEvent.selectOptions(canvas.getByRole('combobox', { name: 'Sort posts' }), 'Most popular');
		await expect(args.onChange).toHaveBeenLastCalledWith({ keyword: '', hashtag: '', topic: '3', sort: 'popular' });

		await userEvent.selectOptions(canvas.getByRole('combobox', { name: 'Filter by topic' }), 'Bug');
		await expect(args.onChange).toHaveBeenLastCalledWith({ keyword: '', hashtag: '', topic: '5', sort: 'newest' });
		await expect(args.onChange).toHaveBeenCalledTimes(3);
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	args: { filters: { per_page: 10, keyword: '助詞', topic: 3, sort: PostSort.popular } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('button', { name: 'Search' })).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Reset' })).toBeVisible();
	},
};
