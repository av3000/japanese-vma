import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, within } from 'storybook/test';
import { getCatalogueShowMockHandler } from '@/api/generated/catalogue/catalogue.msw';
import { CATALOGUE_UUID, catalogueDetail, failWith } from '../routeFixtures';
import CatalogueDetailsPage from './';

/*
 * The catalogue route itself: it fetches by the `:catalogueId` param through the generated show
 * handler and renders the loading state, the unavailable panel or the catalogue. The page layout
 * is covered by `Pages/CatalogueDetails` (`CatalogueContent`); these stories are the states around it.
 */

const meta = {
	title: 'Pages/CatalogueDetails/Route states',
	component: CatalogueDetailsPage,
	parameters: {
		layout: 'fullscreen',
		router: { path: '/catalogues/:catalogueId', initialEntries: [`/catalogues/${CATALOGUE_UUID}`] },
	},
} satisfies Meta<typeof CatalogueDetailsPage>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Loaded: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getCatalogueShowMockHandler(catalogueDetail()));
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('heading', { level: 1 })).toHaveTextContent(
			'N3 kanji for the news',
		);
	},
};

/** The catalogue request never answers: the route announces the loading state. */
export const Loading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getCatalogueShowMockHandler(async () => {
				await delay('infinite');

				return catalogueDetail();
			}),
		);
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('status')).toHaveTextContent('Loading page.');
	},
};

/** A 404 says the catalogue does not exist and links back to the list. */
export const NotFound: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getCatalogueShowMockHandler(() => failWith(404)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(
			await canvas.findByRole('heading', {
				level: 1,
				name: "This catalogue doesn't exist. It may have been deleted.",
			}),
		).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Back to Catalogues' })).toHaveAttribute('href', '/catalogues');
	},
};

/** A 403: a private catalogue someone else owns says it is private, not that it is missing. */
export const Private: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getCatalogueShowMockHandler(() => failWith(403)));
	},
	play: async ({ canvasElement }) => {
		await expect(
			await within(canvasElement).findByRole('heading', { level: 1, name: 'This catalogue is private.' }),
		).toBeVisible();
	},
};

/** A 500 asks the reader to try again, in words that blame neither the catalogue nor the reader. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(getCatalogueShowMockHandler(() => failWith(500)));
	},
	play: async ({ canvasElement }) => {
		await expect(
			await within(canvasElement).findByRole('heading', {
				level: 1,
				name: "This catalogue couldn't be loaded. Please try again.",
			}),
		).toBeVisible();
	},
};
