import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { getCatalogueShowMockHandler, getCatalogueUpdateMockHandler } from '@/api/generated/catalogue/catalogue.msw';
import type { CatalogueResource } from '@/api/generated/model/catalogueResource';
import type { UpdateCatalogueRequest } from '@/api/generated/model/updateCatalogueRequest';
import { currentLocation } from '@/storybook/decorators';
import { users } from '@/storybook/fixtures/users';
import { CATALOGUE_UUID, catalogueDetail, failWith, rejectWith } from '../routeFixtures';
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

/** The catalogue request never answers: the route shows its loading state, not the form. */
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
		await expect(await within(canvasElement).findByRole('status')).toBeVisible();
		await expect(within(canvasElement).queryByLabelText('Title')).toBeNull();
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
 * Another member opens the edit address. The route has no owner check: it shows the form for any
 * signed-in viewer, and the server refuses the save with a 403, which the form reports.
 */
export const NotOwner: Story = {
	parameters: { auth: { user: users.other } },
	beforeEach: ({ msw }) => {
		msw.use(
			// The default fixture belongs to `users.member`, who is not the viewer here.
			getCatalogueUpdateMockHandler(() => failWith(403)),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await changeTitleAndSave(canvas);

		await expect(await canvas.findByRole('alert')).toHaveTextContent("You don't have permission to change this.");
		await expect(currentLocation()).toBe(EDIT_PATH);
	},
};

/** A 422 puts each message under its field and moves focus to the first one. Nothing navigates. */
export const ServerValidation: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getCatalogueUpdateMockHandler(() =>
				rejectWith({
					title: ['The list title must be at least 2 characters'],
					type: ["The type can't be changed once the catalogue has items."],
				}),
			),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await changeTitleAndSave(canvas);

		await expect(await canvas.findByRole('alert')).toHaveTextContent('Check the highlighted fields and try again.');
		await waitFor(() => expect(canvas.getByLabelText('Title')).toHaveFocus());
		await expect(canvas.getByLabelText('Title')).toHaveAccessibleDescription(/at least 2 characters/);
		await expect(canvas.getByRole('group', { name: 'Type' })).toHaveAccessibleDescription(/can't be changed/);
		await expect(currentLocation()).toBe(EDIT_PATH);
	},
};

/** A 500 shows the general error above the form, keeps the edit and does not navigate. */
export const SubmitError: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getCatalogueUpdateMockHandler(() => failWith(500)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await changeTitleAndSave(canvas);

		await expect(await canvas.findByRole('alert')).toHaveTextContent('Something went wrong. Please try again.');
		await expect(canvas.getByLabelText('Title')).toHaveValue('N3 kanji for the news N4');
		await expect(currentLocation()).toBe(EDIT_PATH);
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('button', { name: 'Save changes' })).toBeVisible();
	},
};
