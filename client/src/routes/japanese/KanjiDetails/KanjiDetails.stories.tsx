import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, within } from 'storybook/test';
import { getKanjiShowMockHandler } from '@/api/generated/kanji/kanji.msw';
import { kanjiPerson } from '@/components/features/japanese/related/dictionaryDetail.fixtures';
import { kanjiRelatedSeeds } from '@/components/features/japanese/related/relatedSeeds';
import { failWith } from '../../routeFixtures';
import KanjiDetails from './';

/*
 * The kanji route itself: it fetches by the `:kanji_id` param through the generated show handler
 * and renders the loading state, the unavailable panel or the kanji. The page layout is covered by
 * `Pages/KanjiDetails` (`KanjiContent`). Dictionary entries are public, so there is no private state.
 */

const meta = {
	title: 'Pages/KanjiDetails/Route states',
	component: KanjiDetails,
	parameters: {
		layout: 'fullscreen',
		router: { path: '/kanji/:kanji_id', initialEntries: [`/kanji/${kanjiPerson.uuid}`] },
	},
} satisfies Meta<typeof KanjiDetails>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Loaded: Story = {
	parameters: { seeds: kanjiRelatedSeeds(kanjiPerson, { words: 2398, sentences: 49419, articles: 2 }) },
	beforeEach: ({ msw }) => {
		msw.use(getKanjiShowMockHandler(kanjiPerson));
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('heading', { level: 1 })).toHaveTextContent('人');
	},
};

/** The kanji request never answers: the route announces the loading state. */
export const Loading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getKanjiShowMockHandler(async () => {
				await delay('infinite');

				return kanjiPerson;
			}),
		);
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('status')).toHaveTextContent('Loading page.');
	},
};

/** A 404 says the kanji does not exist and links back to the list. */
export const NotFound: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getKanjiShowMockHandler(() => failWith(404)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(
			await canvas.findByRole('heading', {
				level: 1,
				name: "This kanji doesn't exist. It may have been deleted.",
			}),
		).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Back to Kanji' })).toHaveAttribute('href', '/kanjis');
	},
};

/** A 500 asks the reader to try again, in words that blame neither the kanji nor the reader. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(getKanjiShowMockHandler(() => failWith(500)));
	},
	play: async ({ canvasElement }) => {
		await expect(
			await within(canvasElement).findByRole('heading', {
				level: 1,
				name: "This kanji couldn't be loaded. Please try again.",
			}),
		).toBeVisible();
	},
};
