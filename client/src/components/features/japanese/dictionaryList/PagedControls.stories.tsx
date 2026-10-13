import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, within } from 'storybook/test';
import type { PagedIndex } from '@/api/pagedIndex';
import { PagedControls, PagedListFilters } from './PagedControls';

/** A list as `usePagedIndex` returns it, with the setters as spies. `rows` only needs a length. */
const makeList = (overrides: Partial<PagedIndex<unknown>> = {}): PagedIndex<unknown> => ({
	rows: Array.from({ length: 20 }, (_, index) => ({ id: index })),
	total: 171,
	page: 1,
	pageCount: 9,
	setPage: fn(),
	perPage: 20,
	pageSizes: [20, 50, 100],
	setPerPage: fn(),
	keyword: '',
	setKeyword: fn(),
	isPending: false,
	isFetching: false,
	isError: false,
	...overrides,
});

const meta = {
	title: 'Features/Japanese/PagedControls',
	component: PagedControls,
	parameters: { layout: 'padded' },
	args: { list: makeList(), noun: 'kanji', label: 'Kanji pages' },
	argTypes: { list: { control: false } },
} satisfies Meta<typeof PagedControls>;

export default meta;

type Story = StoryObj<typeof meta>;

/** "Showing 1–20 of 171 kanji": Previous is disabled, Next goes to page 2. */
export const FirstPage: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('status')).toHaveTextContent('Showing 1–20 of 171 kanji');
		await expect(canvas.getByRole('button', { name: 'Previous page' })).toBeDisabled();
		await expect(canvas.getByRole('button', { name: 'Page 1' })).toHaveAttribute('aria-current', 'page');

		await userEvent.click(canvas.getByRole('button', { name: 'Next page' }));
		await expect(args.list.setPage).toHaveBeenCalledWith(2);
	},
};

/** A gap hides the pages between the ends and the current page. */
export const Middle: Story = {
	args: { list: makeList({ page: 5 }) },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('status')).toHaveTextContent('Showing 81–100 of 171 kanji');
		await expect(canvas.getByRole('button', { name: 'Previous page' })).toBeEnabled();
		await expect(canvas.getByRole('button', { name: 'Next page' })).toBeEnabled();

		await userEvent.click(canvas.getByRole('button', { name: 'Previous page' }));
		await expect(args.list.setPage).toHaveBeenLastCalledWith(4);
		await userEvent.click(canvas.getByRole('button', { name: 'Next page' }));
		await expect(args.list.setPage).toHaveBeenLastCalledWith(6);
		await userEvent.click(canvas.getByRole('button', { name: 'Page 9' }));
		await expect(args.list.setPage).toHaveBeenLastCalledWith(9);
	},
};

/** The last page is short: "Showing 161–171", and Next is disabled. */
export const LastPage: Story = {
	args: { list: makeList({ page: 9, rows: Array.from({ length: 11 }, (_, index) => ({ id: index })) }) },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('status')).toHaveTextContent('Showing 161–171 of 171 kanji');
		await expect(canvas.getByRole('button', { name: 'Next page' })).toBeDisabled();
		await expect(canvas.getByRole('button', { name: 'Previous page' })).toBeEnabled();
	},
};

/** One page: the summary stays, and the page navigation is not rendered. */
export const SinglePage: Story = {
	args: {
		list: makeList({ total: 12, pageCount: 1, rows: Array.from({ length: 12 }, (_, index) => ({ id: index })) }),
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('status')).toHaveTextContent('Showing 1–12 of 12 kanji');
		await expect(canvas.queryByRole('navigation', { name: 'Kanji pages' })).not.toBeInTheDocument();
	},
};

/** No results: an empty status line and no pages. */
export const NoResults: Story = {
	args: { list: makeList({ total: 0, pageCount: 1, rows: [] }) },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('status')).toBeEmptyDOMElement();
	},
};

/** A failed load says so in words, and the pages stay where they were. */
export const LoadFailed: Story = {
	args: { list: makeList({ page: 3, isError: true }) },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('status')).toHaveTextContent(
			'The kanji could not be loaded. Try again in a moment.',
		);
	},
};

export const Mobile: Story = {
	args: { list: makeList({ page: 5 }) },
	globals: { viewport: { value: 'xs' } },
};

/** The filter row above the table, from the same file: search plus page size, both back to page 1. */
export const Filters: Story = {
	render: () => (
		<PagedListFilters list={makeList({ keyword: '水' })} noun="kanji" placeholder="Kanji, reading or meaning" />
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('searchbox', { name: 'Search kanji' })).toHaveValue('水');
		await expect(canvas.getByRole('combobox', { name: 'Per page' })).toHaveValue('20');
	},
};
