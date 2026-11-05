import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import type { SearchCountsResource } from '@/api/generated/model';
import { getSearchCountsIndexMockHandler } from '@/api/generated/search-counts/search-counts.msw';
import { currentLocation } from '@/storybook/decorators';
import { HeaderSearchDrawer } from './HeaderSearchDrawer';
import { HeaderSearchPanel } from './HeaderSearchPanel';
import { SEARCH_MEMORY_KEY, type SearchMemory } from './recentSearches';

const RECENT: SearchMemory = {
	lastScope: 'articles',
	recent: [
		{ scope: 'kanji', query: '水' },
		{ scope: 'articles', query: 'grammar' },
		{ scope: 'words', query: 'たべる' },
	],
};

/**
 * What the counts endpoint answers per query (`water` as measured on the local backend). A query
 * not listed here has no hits anywhere. `null` is a list that rejects the query: the article
 * search needs two characters.
 */
const COUNTS: Record<string, Omit<SearchCountsResource, 'q'>> = {
	水: { articles: null, kanji: 1, words: 312, sentences: 87, radicals: 1 },
	たべる: { articles: 0, kanji: 0, words: 14, sentences: 203, radicals: 0 },
	water: { articles: 0, kanji: 108, words: 1256, sentences: 0, radicals: 1 },
};

const NO_HITS = { articles: 0, kanji: 0, words: 0, sentences: 0, radicals: 0 };

const answerFor = (request: Request): SearchCountsResource => {
	const q = new URL(request.url).searchParams.get('q')?.trim() ?? '';
	return { q, ...(COUNTS[q] ?? NO_HITS) };
};

const rowNames = (listbox: HTMLElement) =>
	within(listbox)
		.getAllByRole('option')
		.map((option) => option.textContent?.replace('↵', ''));

/** Each story starts from a known memory and leaves the real one as it found it. */
const withMemory = (memory: SearchMemory | null) => () => {
	const previous = localStorage.getItem(SEARCH_MEMORY_KEY);
	if (memory) localStorage.setItem(SEARCH_MEMORY_KEY, JSON.stringify(memory));
	else localStorage.removeItem(SEARCH_MEMORY_KEY);

	return () => {
		if (previous === null) localStorage.removeItem(SEARCH_MEMORY_KEY);
		else localStorage.setItem(SEARCH_MEMORY_KEY, previous);
	};
};

const meta = {
	title: 'Features/Navigation/HeaderSearch',
	component: HeaderSearchPanel,
	parameters: { layout: 'padded' },
	beforeEach: ({ msw }) => {
		msw.use(getSearchCountsIndexMockHandler(({ request }) => answerFor(request)));
		return withMemory(null)();
	},
	decorators: [
		(Story) => (
			// The panel is absolutely positioned under the input; give it room to open into.
			<div style={{ display: 'flex', minHeight: '28rem', maxWidth: '64rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof HeaderSearchPanel>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Closed: Story = {
	play: async ({ canvasElement }) => {
		const input = within(canvasElement).getByRole('combobox', { name: 'Search JPLearning' });
		await expect(input).toHaveAttribute('aria-expanded', 'false');
		await expect(within(canvasElement).getByRole('search')).toBeInTheDocument();
	},
};

/**
 * One kanji: Kanji has a hit, so it stays the best match even though Words has more. The article
 * search needs two characters, so its row has no number but still runs.
 */
export const SingleKanji: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.type(canvas.getByRole('combobox', { name: 'Search JPLearning' }), '水');

		await expect(canvas.getByRole('button', { name: 'Kanji Best match', pressed: true })).toBeInTheDocument();
		await waitFor(() =>
			expect(rowNames(canvas.getByRole('listbox'))).toEqual([
				'Kanji for 水 · single character detected1 result',
				'Articles with 水 in the title',
				'Words containing 水312 results',
				'Sentences with 水87 results',
				'Radicals matching 水1 result',
			]),
		);
		const options = within(canvas.getByRole('listbox')).getAllByRole('option');
		await expect(options[0]).toHaveAccessibleName('Kanji for 水 · single character detected 1 result');
		await expect(options[0]).toHaveAttribute('aria-selected', 'true');
		await expect(canvas.getByRole('button', { name: 'Any', pressed: true })).toBeInTheDocument();
		await userEvent.click(canvas.getByRole('button', { name: 'N5' }));
		await expect(canvas.getByRole('button', { name: 'N5', pressed: true })).toBeInTheDocument();
	},
};

/** Kana: Words is the best match; Words has no refine row until #399. */
export const Kana: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.type(canvas.getByRole('combobox', { name: 'Search JPLearning' }), 'たべる');

		await expect(canvas.getByRole('button', { name: 'Words Best match', pressed: true })).toBeInTheDocument();
		await waitFor(() =>
			expect(within(canvas.getByRole('listbox')).getAllByRole('option')[0]).toHaveAccessibleName(
				'Words containing たべる · Japanese text detected 14 results',
			),
		);
		await expect(canvas.queryByText(/^Refine/)).toBeNull();
	},
};

/**
 * Latin text: detection alone makes no claim, because English meanings match on Words and Kanji.
 * Once the counts arrive, Words is the first scope in meaning order with hits, so it becomes the
 * best match. A zero stays on its row.
 */
export const Latin: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.type(canvas.getByRole('combobox', { name: 'Search JPLearning' }), 'water');

		await expect(
			await canvas.findByRole('button', { name: 'Words Best match', pressed: true }),
		).toBeInTheDocument();
		await expect(rowNames(canvas.getByRole('listbox'))).toEqual([
			'Words containing water · searches English meanings1,256 results',
			'Kanji for water108 results',
			'Radicals matching water1 result',
			'Sentences with water0 results',
			'Articles with water in the title0 results',
		]);
	},
};

/** No scope has hits: no "Best match" claim, and every row says 0 and still runs. */
export const NoResults: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.type(canvas.getByRole('combobox', { name: 'Search JPLearning' }), 'zzz');

		await waitFor(() =>
			expect(rowNames(canvas.getByRole('listbox'))[0]).toBe(
				'Words containing zzz · searches English meanings0 results',
			),
		);
		await expect(canvas.queryByText('Best match')).toBeNull();
	},
};

/** While the counts load, the rows keep detection's order and the count slot stays empty. */
export const CountsLoading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getSearchCountsIndexMockHandler(async ({ request }) => {
				await delay('infinite');

				return answerFor(request);
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.type(canvas.getByRole('combobox', { name: 'Search JPLearning' }), 'water');

		await expect(canvas.queryByText('Best match')).toBeNull();
		await expect(rowNames(canvas.getByRole('listbox'))[0]).toBe(
			'Words containing water · searches English meanings',
		);
	},
};

/** The counts request fails: the panel works on detection alone, as it did before counts. */
export const CountsFailed: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getSearchCountsIndexMockHandler(() => {
				throw new Response(null, { status: 500 });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.type(canvas.getByRole('combobox', { name: 'Search JPLearning' }), '水');

		await expect(canvas.getByRole('button', { name: 'Kanji Best match', pressed: true })).toBeInTheDocument();
		// Past the debounce and the failed request.
		await new Promise((resolve) => setTimeout(resolve, 600));
		await expect(rowNames(canvas.getByRole('listbox'))).toEqual([
			'Kanji for 水 · single character detected',
			'Articles with 水 in the title',
			'Words containing 水',
			'Sentences with 水',
			'Radicals matching 水',
		]);
	},
};

/** An empty query shows recent searches and the Articles JLPT refine. */
export const RecentSearches: Story = {
	beforeEach: withMemory(RECENT),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('combobox', { name: 'Search JPLearning' }));

		const recent = within(canvas.getByRole('list', { name: 'Recent searches' })).getAllByRole('button');
		await expect(recent.map((button) => button.textContent)).toEqual([
			'水 · Kanji',
			'grammar · Articles',
			'たべる · Words',
		]);
		await expect(canvas.getByRole('list', { name: 'Refine articles · JLPT levels' })).toBeInTheDocument();
	},
};

/** ↓ then Enter runs the second row; Escape closes the panel and keeps focus in the input. */
export const Keyboard: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const input = canvas.getByRole('combobox', { name: 'Search JPLearning' });

		await userEvent.type(input, '水');
		await userEvent.keyboard('{Escape}');
		await expect(input).toHaveAttribute('aria-expanded', 'false');
		await expect(input).toHaveFocus();

		await userEvent.keyboard('{ArrowDown}');
		await expect(input).toHaveAttribute('aria-expanded', 'true');
		await userEvent.keyboard('{ArrowDown}');
		const options = within(canvas.getByRole('listbox')).getAllByRole('option');
		await expect(input).toHaveAttribute('aria-activedescendant', options[1].id);
		await userEvent.keyboard('{Enter}');

		await expect(currentLocation()).toBe('/articles?q=%E6%B0%B4');
	},
};

/** The mobile drawer: a real modal dialog, closed by Cancel with focus back on the trigger. */
export const Drawer: StoryObj<typeof HeaderSearchDrawer> = {
	render: () => <HeaderSearchDrawer />,
	parameters: { layout: 'fullscreen' },
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const trigger = canvas.getByRole('button', { name: 'Search' });

		await userEvent.click(trigger);
		const dialog = await waitFor(() => {
			const element = canvasElement.querySelector('dialog');
			expect(element?.open).toBe(true);
			return element as HTMLDialogElement;
		});
		const drawer = within(dialog);
		await waitFor(() => expect(drawer.getByRole('searchbox', { name: 'Search JPLearning' })).toHaveFocus());
		await expect(drawer.getByRole('button', { name: 'Browse articles' })).toBeInTheDocument();

		await userEvent.type(drawer.getByRole('searchbox', { name: 'Search JPLearning' }), '水');
		await expect(drawer.getByRole('button', { name: 'Search kanji' })).toBeInTheDocument();

		await userEvent.click(drawer.getByRole('button', { name: 'Cancel' }));
		await waitFor(() => expect(dialog.open).toBe(false));
		await waitFor(() => expect(trigger).toHaveFocus());
	},
};
