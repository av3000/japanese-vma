import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, waitFor, within } from 'storybook/test';
import { getCatalogueResolveLegacyIdMockHandler } from '@/api/generated/catalogue/catalogue.msw';
import { currentLocation } from '@/storybook/decorators';
import { CATALOGUE_UUID, failWith } from '../routeFixtures';
import CatalogueLegacyRedirectsPage from './';

/*
 * The old `/list…` addresses. One page serves all four: it picks the redirect from the path, and
 * the two that carry a numeric id ask the API which catalogue that id was (the generated resolver
 * handler), then replace the address with the UUID one. Each story renders the route at one legacy
 * address and asserts where it ends up; the failure stories assert the notice the visitor sees.
 */

const LEGACY_ID = '12';

const meta = {
	title: 'Pages/CatalogueLegacyRedirects',
	component: CatalogueLegacyRedirectsPage,
	parameters: { layout: 'fullscreen' },
	beforeEach: ({ msw }) => {
		msw.use(getCatalogueResolveLegacyIdMockHandler({ id: Number(LEGACY_ID), uuid: CATALOGUE_UUID }));
	},
} satisfies Meta<typeof CatalogueLegacyRedirectsPage>;

export default meta;

type Story = StoryObj<typeof meta>;

/** `/lists` goes to the catalogue list. */
export const List: Story = {
	parameters: { router: { path: '/lists', initialEntries: ['/lists'] } },
	play: async () => {
		await waitFor(() => expect(currentLocation()).toBe('/catalogues'));
	},
};

/** `/newlist` goes to the new-catalogue form. */
export const Create: Story = {
	parameters: { router: { path: '/newlist', initialEntries: ['/newlist'] } },
	play: async () => {
		await waitFor(() => expect(currentLocation()).toBe('/catalogues/new'));
	},
};

/** `/list/12` resolves the old id, then opens that catalogue by UUID. */
export const Detail: Story = {
	parameters: { router: { path: '/list/:catalogueId', initialEntries: [`/list/${LEGACY_ID}`] } },
	play: async () => {
		await waitFor(() => expect(currentLocation()).toBe(`/catalogues/${CATALOGUE_UUID}`));
	},
};

/** `/list/edit/12` resolves the old id, then opens that catalogue's edit form by UUID. */
export const Edit: Story = {
	parameters: { router: { path: '/list/edit/:catalogueId', initialEntries: [`/list/edit/${LEGACY_ID}`] } },
	play: async () => {
		await waitFor(() => expect(currentLocation()).toBe(`/catalogues/${CATALOGUE_UUID}/edit`));
	},
};

/** While the old id is being resolved the visitor sees a spinner and the address does not change. */
export const Resolving: Story = {
	parameters: { router: { path: '/list/:catalogueId', initialEntries: [`/list/${LEGACY_ID}`] } },
	beforeEach: ({ msw }) => {
		msw.use(
			getCatalogueResolveLegacyIdMockHandler(async () => {
				await delay('infinite');

				return { id: Number(LEGACY_ID), uuid: CATALOGUE_UUID };
			}),
		);
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('img', { name: 'Loading...' })).toBeVisible();
		await expect(currentLocation()).toBe(`/list/${LEGACY_ID}`);
	},
};

/** An id that no longer resolves (404) ends on a notice with the way back, and does not redirect. */
export const NotFound: Story = {
	parameters: { router: { path: '/list/:catalogueId', initialEntries: ['/list/99999'] } },
	beforeEach: ({ msw }) => {
		msw.use(getCatalogueResolveLegacyIdMockHandler(() => failWith(404)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('Catalogue not found or was deleted.')).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Back to all Catalogues' })).toHaveAttribute(
			'href',
			'/catalogues',
		);
		await expect(currentLocation()).toBe('/list/99999');
	},
};

/** An address that is not a number never reaches the API: it goes straight to the same notice. */
export const InvalidId: Story = {
	parameters: { router: { path: '/list/:catalogueId', initialEntries: ['/list/not-a-number'] } },
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByText('Catalogue not found or was deleted.')).toBeVisible();
		await expect(currentLocation()).toBe('/list/not-a-number');
	},
};
