import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { getWordIndexMockHandler } from '@/api/generated/word/word.msw';
import { DICTIONARY_PER_PAGE } from '@/components/features/japanese/dictionaryList';
import { repeatRows, wordRows } from '@/components/shared/DataTable/DataTable.fixtures';
import { currentLocation } from '@/storybook/decorators';
import { users } from '@/storybook/fixtures/users';
import { createRequestLog, pageOf } from '../../pageFixtures';
import WordsList from './';

/*
 * `/words` on its real route component and hooks: the URL owns the keyword, and the index request
 * goes through the generated MSW handler. The list pages by Load more (an infinite query), so
 * there is no `page` in the URL; the paged story asks for the next pages.
 */

const TOTAL = 60;

const rows = repeatRows(wordRows, TOTAL);
const rowAt = (position: number) => rows[position - 1];

const requests = createRequestLog();

/** Answers every index request with `total` rows, and records its query string. */
const wordIndex = (total = TOTAL) =>
	getWordIndexMockHandler(({ request }) => {
		requests.record(request);

		return pageOf(request, total, rowAt, DICTIONARY_PER_PAGE);
	});

const meta = {
	title: 'Pages/Word list',
	component: WordsList,
	parameters: {
		layout: 'fullscreen',
		router: { path: '/words', initialEntries: ['/words'] },
	},
	beforeEach: ({ msw }) => {
		requests.clear();
		msw.use(wordIndex());
	},
} satisfies Meta<typeof WordsList>;

export default meta;

type Story = StoryObj<typeof meta>;

/** The first page of 60: 25 word rows (the 200-character meaning and the 37-character reading among them) and Load more. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('Showing 25 of 60')).toBeVisible();
		await expect(canvas.getByRole('heading', { level: 1, name: 'Words' })).toBeVisible();
		await expect(within(canvas.getByRole('table', { name: 'Words' })).getAllByRole('row')).toHaveLength(26);
		await expect(canvas.getByRole('button', { name: 'Load more' })).toBeVisible();

		await expect(requests.searches).toHaveLength(1);
		await expect(requests.last()).toMatchObject({
			page: '1',
			per_page: '25',
			include: 'viewer_catalogue_state',
		});
		await expect(requests.last().keyword).toBeUndefined();
	},
};

export const Guest: Story = {
	parameters: { auth: 'guest' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await canvas.findByText('Showing 25 of 60');

		await expect(canvas.queryByRole('button', { name: /^Save/ })).not.toBeInTheDocument();
	},
};

/** A signed-in viewer gets a Save button on every row, from the viewer state the list payload carries. */
export const Member: Story = {
	parameters: { auth: { user: users.member } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await canvas.findByText('Showing 25 of 60');

		await expect(canvas.getAllByRole('button', { name: /^Save/ }).length).toBeGreaterThan(0);
		await expect(canvas.getAllByRole('button', { name: /^Saved:/ }).length).toBeGreaterThan(0);
	},
};

/** A shared link: the keyword in the URL reaches the request, the search box and the count line. */
export const Filtered: Story = {
	parameters: {
		router: { path: '/words', initialEntries: ['/words?keyword=%E9%81%8A%E3%81%B6'] },
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('Showing 25 of 60 · keyword: 遊ぶ')).toBeVisible();
		await expect(canvas.getByRole('searchbox', { name: 'Search words by keyword' })).toHaveValue('遊ぶ');
		await expect(requests.last()).toMatchObject({ keyword: '遊ぶ', per_page: '25' });
	},
};

/** Typing waits for Enter, then rewrites the URL and sends a new request carrying the keyword. */
export const FilterChangesRequest: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await canvas.findByText('Showing 25 of 60');

		const requestsBeforeTyping = requests.searches.length;
		await userEvent.type(canvas.getByRole('searchbox', { name: 'Search words by keyword' }), 'flooding');
		await expect(requests.searches).toHaveLength(requestsBeforeTyping);

		await userEvent.type(canvas.getByRole('searchbox', { name: 'Search words by keyword' }), '{Enter}');
		await waitFor(() => expect(requests.last().keyword).toBe('flooding'));
		await expect(requests.last().page).toBe('1');
		await expect(currentLocation()).toBe('/words?keyword=flooding');
	},
};

/** Load more asks for the next page with the same keyword, and the rows append until the last page. */
export const Paged: Story = {
	parameters: { router: { path: '/words', initialEntries: ['/words?keyword=a'] } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await canvas.findByText('Showing 25 of 60 · keyword: a');

		await userEvent.click(canvas.getByRole('button', { name: 'Load more' }));

		await expect(await canvas.findByText('Showing 50 of 60 · keyword: a')).toBeVisible();
		await expect(requests.searches).toHaveLength(2);
		await expect(requests.last()).toMatchObject({ page: '2', per_page: '25', keyword: 'a' });

		await userEvent.click(canvas.getByRole('button', { name: 'Load more' }));
		await expect(await canvas.findByText('Showing 60 of 60 · keyword: a')).toBeVisible();
		await expect(await canvas.findByText('No more results')).toBeVisible();
		await expect(requests.last().page).toBe('3');
	},
};

/** No words at all: the table's empty panel, and no Load more or "No more results" under it. */
export const Empty: Story = {
	beforeEach: ({ msw }) => {
		msw.use(wordIndex(0));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('No words yet')).toBeVisible();
		await expect(canvas.getByText('0 results')).toBeVisible();
		await expect(canvas.queryByText('No more results')).not.toBeInTheDocument();
	},
};

/** A search that matches nothing names the term and suggests kana. */
export const NoResults: Story = {
	parameters: {
		router: { path: '/words', initialEntries: ['/words?keyword=%E3%81%B5%E3%81%97%E3%81%8E%E3%81%AA'] },
	},
	beforeEach: ({ msw }) => {
		msw.use(wordIndex(0));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText(/^No words match/)).toBeVisible();
		await expect(canvas.getByText('Try a shorter search, a reading in kana, or clear the filters.')).toBeVisible();
		await expect(requests.last().keyword).toBe('ふしぎな');
	},
};

/** The index request never answers: the real header and filters over skeleton rows, no count line yet. */
export const Loading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getWordIndexMockHandler(async ({ request }) => {
				await delay('infinite');

				return pageOf(request, 0, rowAt, DICTIONARY_PER_PAGE);
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByRole('table', { name: 'Words' })).toHaveAttribute('aria-busy', 'true');
		await expect(canvas.getByRole('heading', { level: 1, name: 'Words' })).toBeVisible();
		await expect(canvas.queryByText(/Showing/)).not.toBeInTheDocument();
	},
};

/** The index request fails with a 500: a fixed message with a retry, never the raw error. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(
			getWordIndexMockHandler(() => {
				throw new Response(null, { status: 500 });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('Words could not be loaded.')).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Try again' })).toBeVisible();
		await expect(canvas.queryByRole('table')).not.toBeInTheDocument();
	},
};

/** Stacked rows at 360px: the word and its reading, then the meaning, types and level. */
export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByText('Showing 25 of 60')).toBeVisible();
	},
};

export const MobileMember: Story = {
	parameters: { auth: { user: users.member } },
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect((await within(canvasElement).findAllByRole('button', { name: /^Save/ })).length).toBeGreaterThan(
			0,
		);
	},
};
