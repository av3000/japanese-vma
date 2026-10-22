import type { Meta, StoryObj } from '@storybook/react-vite';
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

/** `/list/12` resolves the old id, then opens that catalogue by UUID. */
export const Detail: Story = {
	parameters: { router: { path: '/list/:catalogueId', initialEntries: [`/list/${LEGACY_ID}`] } },
	play: async () => {
		await waitFor(() => expect(currentLocation()).toBe(`/catalogues/${CATALOGUE_UUID}`));
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
