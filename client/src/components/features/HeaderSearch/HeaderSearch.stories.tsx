import type { Meta, StoryObj } from '@storybook/react';
import { expect, userEvent, waitFor, within } from '@storybook/test';
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
	title: 'Features/HeaderSearch/Panel',
	component: HeaderSearchPanel,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	beforeEach: withMemory(null),
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

/** One kanji: Kanji is the best match, its row is highlighted, and the JLPT refine row shows. */
export const SingleKanji: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.type(canvas.getByRole('combobox', { name: 'Search JPLearning' }), '水');

		await expect(canvas.getByRole('button', { name: 'Kanji Best match', pressed: true })).toBeInTheDocument();
		const options = within(canvas.getByRole('listbox')).getAllByRole('option');
		await expect(options[0]).toHaveAccessibleName('Kanji for 水 · single character detected');
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
		await expect(within(canvas.getByRole('listbox')).getAllByRole('option')[0]).toHaveAccessibleName(
			'Words containing たべる · Japanese text detected',
		);
		await expect(canvas.queryByText(/^Refine/)).toBeNull();
	},
};

/** Latin text goes to Words with no "Best match" claim, ordered by where English meanings match. */
export const Latin: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.type(canvas.getByRole('combobox', { name: 'Search JPLearning' }), 'water');

		await expect(canvas.queryByText('Best match')).toBeNull();
		const names = within(canvas.getByRole('listbox'))
			.getAllByRole('option')
			.map((option) => option.textContent?.replace('↵', ''));
		await expect(names).toEqual([
			'Words containing water · searches English meanings',
			'Kanji for water',
			'Radicals matching water',
			'Sentences with water',
			'Articles with water in the title',
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
		const startUrl = window.location.href;
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

		await expect(`${window.location.pathname}${window.location.search}`).toBe('/articles?q=%E6%B0%B4');
		// The preview's BrowserRouter pushed a real history entry; put the story URL back.
		window.history.replaceState(window.history.state, '', startUrl);
	},
};

/** The mobile drawer: a real modal dialog, closed by Cancel with focus back on the trigger. */
export const Drawer: StoryObj<typeof HeaderSearchDrawer> = {
	render: () => <HeaderSearchDrawer />,
	parameters: { viewport: { defaultViewport: 'mobile1' }, layout: 'fullscreen' },
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
