import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, within } from 'storybook/test';
import { getWordShowMockHandler } from '@/api/generated/word/word.msw';
import { wordManyMeanings } from '@/components/features/japanese/related/dictionaryDetail.fixtures';
import { wordRelatedSeeds } from '@/components/features/japanese/related/relatedSeeds';
import { failWith } from '../../routeFixtures';
import WordDetails from './';

/*
 * The word route itself: it fetches by the `:word_id` param through the generated show handler and
 * renders the loading state, the unavailable panel or the word. The page layout is covered by
 * `Pages/WordDetails` (`WordContent`). Dictionary entries are public, so there is no private state.
 */

const meta = {
	title: 'Pages/WordDetails/Route states',
	component: WordDetails,
	parameters: {
		layout: 'fullscreen',
		router: { path: '/word/:word_id', initialEntries: [`/word/${wordManyMeanings.uuid}`] },
	},
} satisfies Meta<typeof WordDetails>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Loaded: Story = {
	parameters: { seeds: wordRelatedSeeds(wordManyMeanings.id, 2) },
	beforeEach: ({ msw }) => {
		msw.use(getWordShowMockHandler(wordManyMeanings));
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('heading', { level: 1 })).toHaveTextContent('出る');
	},
};

/** The word request never answers: the route announces the loading state. */
export const Loading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getWordShowMockHandler(async () => {
				await delay('infinite');

				return wordManyMeanings;
			}),
		);
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('status')).toHaveTextContent('Loading page.');
	},
};

/** A 404 says the word does not exist and links back to the list. */
export const NotFound: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getWordShowMockHandler(() => failWith(404)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(
			await canvas.findByRole('heading', {
				level: 1,
				name: "This word doesn't exist. It may have been deleted.",
			}),
		).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Back to Words' })).toHaveAttribute('href', '/words');
	},
};

/** A 500 asks the reader to try again, in words that blame neither the word nor the reader. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(getWordShowMockHandler(() => failWith(500)));
	},
	play: async ({ canvasElement }) => {
		await expect(
			await within(canvasElement).findByRole('heading', {
				level: 1,
				name: "This word couldn't be loaded. Please try again.",
			}),
		).toBeVisible();
	},
};
