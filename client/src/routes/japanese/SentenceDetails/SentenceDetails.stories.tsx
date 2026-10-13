import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, within } from 'storybook/test';
import { getSentenceShowMockHandler } from '@/api/generated/sentence/sentence.msw';
import { sentenceShort } from '@/components/features/japanese/related/dictionaryDetail.fixtures';
import { sentenceCommentSeeds } from '@/components/features/japanese/related/relatedSeeds';
import { failWith } from '../../routeFixtures';
import SentenceDetails from './';

/*
 * The sentence route itself: it fetches by the `:sentence_id` param through the generated show
 * handler and renders the loading state, the unavailable panel or the sentence. The page layout is
 * covered by `Pages/SentenceDetails` (`SentenceContent`). Sentences are public, so there is no
 * private state.
 */

const meta = {
	title: 'Pages/SentenceDetails/Route states',
	component: SentenceDetails,
	parameters: {
		layout: 'fullscreen',
		router: { path: '/sentence/:sentence_id', initialEntries: [`/sentence/${sentenceShort.uuid}`] },
	},
} satisfies Meta<typeof SentenceDetails>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Loaded: Story = {
	parameters: { seeds: sentenceCommentSeeds(sentenceShort.uuid) },
	beforeEach: ({ msw }) => {
		msw.use(getSentenceShowMockHandler(sentenceShort));
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('heading', { level: 1 })).toHaveTextContent(
			'親友は何人いる？',
		);
	},
};

/** The sentence request never answers: the route announces the loading state. */
export const Loading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getSentenceShowMockHandler(async () => {
				await delay('infinite');

				return sentenceShort;
			}),
		);
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('status')).toHaveTextContent('Loading page.');
	},
};

/** A 404 says the sentence does not exist and links back to the list. */
export const NotFound: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getSentenceShowMockHandler(() => failWith(404)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(
			await canvas.findByRole('heading', {
				level: 1,
				name: "This sentence doesn't exist. It may have been deleted.",
			}),
		).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Back to Sentences' })).toHaveAttribute('href', '/sentences');
	},
};

/** A 500 asks the reader to try again, in words that blame neither the sentence nor the reader. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(getSentenceShowMockHandler(() => failWith(500)));
	},
	play: async ({ canvasElement }) => {
		await expect(
			await within(canvasElement).findByRole('heading', {
				level: 1,
				name: "This sentence couldn't be loaded. Please try again.",
			}),
		).toBeVisible();
	},
};
