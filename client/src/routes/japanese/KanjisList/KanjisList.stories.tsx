import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { getKanjiIndexMockHandler } from '@/api/generated/kanji/kanji.msw';
import { DICTIONARY_PER_PAGE } from '@/components/features/japanese/dictionaryList';
import { kanjiRows, repeatRows } from '@/components/shared/DataTable/DataTable.fixtures';
import { currentLocation } from '@/storybook/decorators';
import { users } from '@/storybook/fixtures/users';
import { createRequestLog, pageOf } from '../../pageFixtures';
import KanjisList from './';

/*
 * `/kanjis` on its real route component and hooks: the URL owns the keyword and JLPT level, and
 * the index request goes through the generated MSW handler. The list pages by Load more (an
 * infinite query), so there is no `page` in the URL; the paged story asks for the next pages.
 */

const TOTAL = 60;

const rows = repeatRows(kanjiRows, TOTAL);
const rowAt = (position: number) => rows[position - 1];

const requests = createRequestLog();

/** Answers every index request with `total` rows, and records its query string. */
const kanjiIndex = (total = TOTAL) =>
	getKanjiIndexMockHandler(({ request }) => {
		requests.record(request);

		return pageOf(request, total, rowAt, DICTIONARY_PER_PAGE);
	});

const searchOf = (location: string) => new URLSearchParams(location.split('?')[1]);

const meta = {
	title: 'Pages/Kanji list/Page',
	component: KanjisList,
	parameters: {
		layout: 'fullscreen',
		router: { path: '/kanjis', initialEntries: ['/kanjis'] },
	},
	beforeEach: ({ msw }) => {
		requests.clear();
		msw.use(kanjiIndex());
	},
} satisfies Meta<typeof KanjisList>;

export default meta;

type Story = StoryObj<typeof meta>;

/** The first page of 60: 25 kanji rows and Load more. A guest has no Save column. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('Showing 25 of 60')).toBeVisible();
		await expect(canvas.getByRole('heading', { level: 1, name: 'Kanji' })).toBeVisible();

		const table = canvas.getByRole('table', { name: 'Kanji' });
		await expect(within(table).getAllByRole('row')).toHaveLength(26);
		await expect(canvas.getByRole('button', { name: 'Load more' })).toBeVisible();

		await expect(requests.searches).toHaveLength(1);
		await expect(requests.last()).toMatchObject({
			page: '1',
			per_page: '25',
			include: 'viewer_catalogue_state',
		});
		await expect(requests.last().keyword).toBeUndefined();
		await expect(requests.last().jlpt).toBeUndefined();
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

/** A shared link: keyword and JLPT level in the URL reach the request, the controls and the count line. */
export const Filtered: Story = {
	parameters: {
		router: { path: '/kanjis', initialEntries: ['/kanjis?keyword=%E6%B0%B4&jlpt=5'] },
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('Showing 25 of 60 · keyword: 水 · JLPT: N5')).toBeVisible();
		await expect(canvas.getByRole('searchbox', { name: 'Search kanji by keyword' })).toHaveValue('水');
		await expect(canvas.getByRole('combobox', { name: 'JLPT level' })).toHaveValue('5');

		await expect(requests.last()).toMatchObject({ keyword: '水', jlpt: '5', per_page: '25' });
	},
};

/** The "-" level the API uses for kanji with no JLPT level is offered as "Uncommon". */
export const FilteredUncommon: Story = {
	parameters: { router: { path: '/kanjis', initialEntries: ['/kanjis?jlpt=-'] } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('Showing 25 of 60 · JLPT: Uncommon')).toBeVisible();
		await expect(canvas.getByRole('combobox', { name: 'JLPT level' })).toHaveValue('-');
		await expect(requests.last().jlpt).toBe('-');
	},
};

/** A level applies at once; the keyword waits for Enter. Each rewrites the URL and sends a new request. */
export const FilterChangesRequest: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await canvas.findByText('Showing 25 of 60');

		await userEvent.selectOptions(canvas.getByRole('combobox', { name: 'JLPT level' }), 'N3');
		await waitFor(() => expect(requests.last().jlpt).toBe('3'));
		await expect(searchOf(currentLocation()).get('jlpt')).toBe('3');

		const requestsBeforeTyping = requests.searches.length;
		await userEvent.type(canvas.getByRole('searchbox', { name: 'Search kanji by keyword' }), '海');
		await expect(requests.searches).toHaveLength(requestsBeforeTyping);

		await userEvent.type(canvas.getByRole('searchbox', { name: 'Search kanji by keyword' }), '{Enter}');
		await waitFor(() => expect(requests.last().keyword).toBe('海'));
		await expect(requests.last()).toMatchObject({ jlpt: '3', page: '1' });
		await expect(currentLocation()).toBe(`/kanjis?keyword=${encodeURIComponent('海')}&jlpt=3`);
	},
};

/** Load more asks for the next page with the same filters, and the rows append until the last page. */
export const Paged: Story = {
	parameters: { router: { path: '/kanjis', initialEntries: ['/kanjis?jlpt=5'] } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await canvas.findByText('Showing 25 of 60 · JLPT: N5');

		await userEvent.click(canvas.getByRole('button', { name: 'Load more' }));

		await expect(await canvas.findByText('Showing 50 of 60 · JLPT: N5')).toBeVisible();
		await expect(requests.searches).toHaveLength(2);
		await expect(requests.last()).toMatchObject({ page: '2', per_page: '25', jlpt: '5' });

		await userEvent.click(canvas.getByRole('button', { name: 'Load more' }));
		await expect(await canvas.findByText('Showing 60 of 60 · JLPT: N5')).toBeVisible();
		await expect(await canvas.findByText('No more results')).toBeVisible();
		await expect(requests.last().page).toBe('3');
	},
};

/** No kanji at all: the table's empty panel, and no Load more or "No more results" under it. */
export const Empty: Story = {
	beforeEach: ({ msw }) => {
		msw.use(kanjiIndex(0));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('No kanji yet')).toBeVisible();
		await expect(canvas.getByText('0 results')).toBeVisible();
		await expect(canvas.queryByText('No more results')).not.toBeInTheDocument();
	},
};

/** A search that matches nothing names the term and suggests kana. */
export const NoResults: Story = {
	parameters: {
		router: { path: '/kanjis', initialEntries: ['/kanjis?keyword=%E3%81%B5%E3%81%97%E3%81%8E%E3%81%AA'] },
	},
	beforeEach: ({ msw }) => {
		msw.use(kanjiIndex(0));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText(/^No kanji match/)).toBeVisible();
		await expect(canvas.getByText('Try a shorter search, a reading in kana, or clear the filters.')).toBeVisible();
		await expect(requests.last().keyword).toBe('ふしぎな');
	},
};

/** A level that matches nothing words itself as "these filters", not as a search. */
export const NoResultsForLevel: Story = {
	parameters: { router: { path: '/kanjis', initialEntries: ['/kanjis?jlpt=1'] } },
	beforeEach: ({ msw }) => {
		msw.use(kanjiIndex(0));
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByText('No kanji match these filters')).toBeVisible();
	},
};

/** The index request never answers: the real header and filters over skeleton rows, no count line yet. */
export const Loading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getKanjiIndexMockHandler(async ({ request }) => {
				await delay('infinite');

				return pageOf(request, 0, rowAt, DICTIONARY_PER_PAGE);
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByRole('table', { name: 'Kanji' })).toHaveAttribute('aria-busy', 'true');
		await expect(canvas.getByRole('heading', { level: 1, name: 'Kanji' })).toBeVisible();
		await expect(canvas.queryByText(/Showing/)).not.toBeInTheDocument();
	},
};

/** The index request fails with a 500: a fixed message with a retry, never the raw error. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(
			getKanjiIndexMockHandler(() => {
				throw new Response(null, { status: 500 });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('Kanji could not be loaded.')).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Try again' })).toBeVisible();
		await expect(canvas.queryByRole('table')).not.toBeInTheDocument();
	},
};

/** Try again after a failure asks the same page again and shows the rows. */
export const RetryAfterError: Story = {
	beforeEach: ({ msw }) => {
		let failed = false;

		msw.use(
			getKanjiIndexMockHandler(({ request }) => {
				requests.record(request);

				if (!failed) {
					failed = true;
					throw new Response(null, { status: 500 });
				}

				return pageOf(request, TOTAL, rowAt, DICTIONARY_PER_PAGE);
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await userEvent.click(await canvas.findByRole('button', { name: 'Try again' }));

		await expect(await canvas.findByText('Showing 25 of 60')).toBeVisible();
		await expect(requests.searches).toHaveLength(2);
	},
};

/** Stacked rows: glyph and meaning, On, Kun, then strokes, level and frequency on one line, at 360px. */
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
