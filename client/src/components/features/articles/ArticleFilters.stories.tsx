import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import { emptyArticleListFilterState } from '@/routes/ArticlesList/articleListSearchParams';
import ArticleFilters from './ArticleFilters';
import { articleFacets } from './fixtures';

const meta = {
	title: 'Features/Articles/ArticleFilters',
	component: ArticleFilters,
	parameters: { layout: 'padded' },
	args: {
		state: emptyArticleListFilterState(),
		facets: articleFacets,
		onSearch: fn(),
		onToggleJlptLevel: fn(),
		onToggleHashtag: fn(),
		onSortChange: fn(),
		onReset: fn(),
	},
} satisfies Meta<typeof ArticleFilters>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Typing fills a draft; only a submit sends it, trimmed and once. */
export const Default: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('search', { name: 'Article filters' })).toBeVisible();
		await expect(canvas.queryByRole('button', { name: 'Reset' })).toBeNull();

		await userEvent.type(canvas.getByRole('searchbox', { name: 'Search articles' }), '  新幹線 ');
		await expect(args.onSearch).not.toHaveBeenCalled();

		await userEvent.click(canvas.getByRole('button', { name: 'Search' }));
		await waitFor(() => expect(args.onSearch).toHaveBeenCalledTimes(1));
		await expect(args.onSearch).toHaveBeenCalledWith('新幹線');
	},
};

/** Enter in the search box submits too. */
export const SubmitsOnEnter: Story = {
	play: async ({ canvasElement, args }) => {
		await userEvent.type(
			within(canvasElement).getByRole('searchbox', { name: 'Search articles' }),
			'station{Enter}',
		);
		await waitFor(() => expect(args.onSearch).toHaveBeenCalledTimes(1));
		await expect(args.onSearch).toHaveBeenCalledWith('station');
	},
};

/** A saved search, two JLPT levels, a hashtag and a sort are all pressed, and Reset is offered. */
export const Filled: Story = {
	args: {
		state: { q: 'shinkansen', jlptLevels: ['n5', 'n3'], hashtagIds: [7], sort: 'title_jp', page: 1 },
	},
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('searchbox', { name: 'Search articles' })).toHaveValue('shinkansen');
		await expect(canvas.getByRole('combobox', { name: 'Sort articles' })).toHaveValue('title_jp');
		await expect(canvas.getByRole('button', { name: 'N3 (21)' })).toHaveAttribute('aria-pressed', 'true');
		await expect(canvas.getByRole('button', { name: 'N4 (8)' })).toHaveAttribute('aria-pressed', 'false');

		await userEvent.click(canvas.getByRole('button', { name: 'N4 (8)' }));
		await expect(args.onToggleJlptLevel).toHaveBeenCalledTimes(1);
		await expect(args.onToggleJlptLevel).toHaveBeenCalledWith('n4');

		await userEvent.click(canvas.getByRole('button', { name: '#news (14)' }));
		await expect(args.onToggleHashtag).toHaveBeenCalledTimes(1);
		await expect(args.onToggleHashtag).toHaveBeenCalledWith(9);

		await userEvent.selectOptions(canvas.getByRole('combobox', { name: 'Sort articles' }), 'Newest first');
		await expect(args.onSortChange).toHaveBeenCalledTimes(1);
		await expect(args.onSortChange).toHaveBeenCalledWith('-created_at');

		await userEvent.click(canvas.getByRole('button', { name: 'Reset' }));
		await expect(args.onReset).toHaveBeenCalledTimes(1);
	},
};

/** One character is below the minimum: the hint says so and the search button stays disabled. */
export const SearchTooShort: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		const search = canvas.getByRole('searchbox', { name: 'Search articles' });
		await userEvent.type(search, 'a{Enter}');

		await expect(search).toHaveAccessibleDescription('Enter at least 2 characters.');
		await expect(canvas.getByRole('button', { name: 'Search' })).toBeDisabled();
		await expect(args.onSearch).not.toHaveBeenCalled();
	},
};

/** The server has no facet counts (or they were not requested): the levels still work, unlabelled by count. */
export const WithoutFacets: Story = {
	args: { facets: [] },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('button', { name: 'N5' })).toBeVisible();
		await expect(canvas.queryByRole('group', { name: 'Hashtags' })).toBeNull();
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	args: { state: { q: 'shinkansen', jlptLevels: ['n3'], hashtagIds: [7], sort: '-created_at', page: 1 } },
};
