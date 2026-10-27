import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { getCatalogueShowMockHandler, getCatalogueUpdateMockHandler } from '@/api/generated/catalogue/catalogue.msw';
import type { CatalogueResource } from '@/api/generated/model/catalogueResource';
import type { UpdateCatalogueRequest } from '@/api/generated/model/updateCatalogueRequest';
import { currentLocation } from '@/storybook/decorators';
import { users } from '@/storybook/fixtures/users';
import { CATALOGUE_UUID, catalogueDetail, failWith } from '../routeFixtures';
import CatalogueEditPage from './';

/*
 * The Edit catalogue route on its real hooks: the generated show handler loads the catalogue and
 * the generated PUT handler saves it. The story asserts the body the handler received (only the
 * fields that changed) and where the route navigated afterwards.
 */

const EDIT_PATH = `/catalogues/${CATALOGUE_UUID}/edit`;

const meta = {
	title: 'Pages/CatalogueEdit',
	component: CatalogueEditPage,
	parameters: {
		layout: 'fullscreen',
		auth: 'member',
		router: { path: '/catalogues/:catalogueId/edit', initialEntries: [EDIT_PATH] },
	},
	beforeEach: ({ msw }) => {
		msw.use(getCatalogueShowMockHandler(catalogueDetail()));
	},
} satisfies Meta<typeof CatalogueEditPage>;

export default meta;

type Story = StoryObj<typeof meta>;

/** What the generated PUT handler received, newest last; cleared before each story that saves. */
const saved: UpdateCatalogueRequest[] = [];

const recordSaves = () =>
	getCatalogueUpdateMockHandler(async ({ request }) => {
		const body = (await request.json()) as UpdateCatalogueRequest;
		saved.push(body);

		return { ...catalogueDetail(), ...body } as unknown as CatalogueResource;
	});

const changeTitleAndSave = async (canvas: ReturnType<typeof within>) => {
	await userEvent.type(await canvas.findByLabelText('Title'), ' N4');
	await userEvent.click(canvas.getByRole('button', { name: 'Save changes' }));
};

/** The catalogue opens in the form; its items lock the type, and only the changed title is sent. */
export const Edit: Story = {
	beforeEach: ({ msw }) => {
		saved.length = 0;
		msw.use(recordSaves());
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByLabelText('Title')).toHaveValue('N3 kanji for the news');
		await expect(canvas.getByRole('heading', { level: 1, name: 'Edit catalogue' })).toBeVisible();

		await changeTitleAndSave(canvas);

		await waitFor(() => expect(currentLocation()).toBe(`/catalogues/${CATALOGUE_UUID}`));
		await expect(saved).toEqual([{ title: 'N3 kanji for the news N4' }]);
	},
};

/** A 404: the route says the catalogue could not be loaded, with the way back. */
export const NotFound: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getCatalogueShowMockHandler(() => failWith(404)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(
			await canvas.findByText('This catalogue could not be loaded. It may have been deleted.'),
		).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Catalogues' })).toHaveAttribute('href', '/catalogues');
	},
};

/**
 * Another member opens the edit address. The default fixture belongs to `users.member`, so the
 * route refuses up front, as the server would refuse the save, and offers the way back.
 */
export const NotOwner: Story = {
	parameters: { auth: { user: users.other } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByRole('alert')).toHaveTextContent(
			'You do not have permission to edit this catalogue.',
		);
		await expect(canvas.queryByLabelText('Title')).toBeNull();
		await expect(canvas.getByRole('link', { name: 'N3 kanji for the news' })).toHaveAttribute(
			'href',
			`/catalogues/${CATALOGUE_UUID}`,
		);
	},
};
