import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, within } from 'storybook/test';
import { getRadicalShowMockHandler } from '@/api/generated/radical/radical.msw';
import { radicalTree } from '@/components/features/japanese/related/dictionaryDetail.fixtures';
import { radicalKanjiSeeds } from '@/components/features/japanese/related/relatedSeeds';
import { failWith } from '../../routeFixtures';
import RadicalDetails from './';

/*
 * The radical route itself: it fetches by the `:radical_id` param through the generated show
 * handler and renders the loading state, the unavailable panel or the radical. The page layout is
 * covered by `Pages/RadicalDetails` (`RadicalContent`). Dictionary entries are public, so there is
 * no private state.
 */

const radical = { ...radicalTree, kanjis: [] };

const meta = {
	title: 'Pages/RadicalDetails/Route states',
	component: RadicalDetails,
	parameters: {
		layout: 'fullscreen',
		router: { path: '/radical/:radical_id', initialEntries: [`/radical/${radicalTree.uuid}`] },
	},
} satisfies Meta<typeof RadicalDetails>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Loaded: Story = {
	parameters: { seeds: radicalKanjiSeeds(radicalTree.uuid, 674) },
	beforeEach: ({ msw }) => {
		msw.use(getRadicalShowMockHandler(radical));
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('heading', { level: 1 })).toHaveTextContent('木');
	},
};

/** The radical request never answers: the route announces the loading state. */
export const Loading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getRadicalShowMockHandler(async () => {
				await delay('infinite');

				return radical;
			}),
		);
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('status')).toHaveTextContent('Loading page.');
	},
};

/** A 404 says the radical does not exist and links back to the list. */
export const NotFound: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getRadicalShowMockHandler(() => failWith(404)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(
			await canvas.findByRole('heading', {
				level: 1,
				name: "This radical doesn't exist. It may have been deleted.",
			}),
		).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Back to Radicals' })).toHaveAttribute('href', '/radicals');
	},
};

/** A 500 asks the reader to try again, in words that blame neither the radical nor the reader. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(getRadicalShowMockHandler(() => failWith(500)));
	},
	play: async ({ canvasElement }) => {
		await expect(
			await within(canvasElement).findByRole('heading', {
				level: 1,
				name: "This radical couldn't be loaded. Please try again.",
			}),
		).toBeVisible();
	},
};
