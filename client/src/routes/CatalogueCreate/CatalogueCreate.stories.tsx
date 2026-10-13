import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { getCatalogueStoreMockHandler } from '@/api/generated/catalogue/catalogue.msw';
import type { StoreCatalogueRequest } from '@/api/generated/model/storeCatalogueRequest';
import { currentLocation } from '@/storybook/decorators';
import { CATALOGUE_UUID, failWith, rejectWith } from '../routeFixtures';
import CatalogueCreatePage from './';

/*
 * The New catalogue route on its real mutation: the generated POST handler answers, and the story
 * asserts the body it received and where the route navigated afterwards. The route only has a
 * create form, so there is no entity to load: no loading, not-found or not-owner state exists.
 */

const meta = {
	title: 'Pages/CatalogueCreate',
	component: CatalogueCreatePage,
	parameters: {
		layout: 'fullscreen',
		auth: 'member',
		router: { path: '/catalogues/new', initialEntries: ['/catalogues/new'] },
	},
} satisfies Meta<typeof CatalogueCreatePage>;

export default meta;

type Story = StoryObj<typeof meta>;

/** What the generated POST handler received, newest last; cleared before each story that posts. */
const posted: StoreCatalogueRequest[] = [];

const fillAndSubmit = async (canvas: ReturnType<typeof within>) => {
	await userEvent.type(canvas.getByLabelText('Title'), 'Station kanji');
	await userEvent.click(canvas.getByRole('radio', { name: 'Kanji' }));
	await userEvent.click(canvas.getByRole('button', { name: 'Create catalogue' }));
};

/** Name it, pick the kanji type and submit: the body carries the chosen type, and the route opens the catalogue. */
export const Create: Story = {
	beforeEach: ({ msw }) => {
		posted.length = 0;

		msw.use(
			getCatalogueStoreMockHandler(async ({ request }) => {
				posted.push((await request.json()) as StoreCatalogueRequest);

				return { uuid: CATALOGUE_UUID };
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 1, name: 'New catalogue' })).toBeVisible();

		await fillAndSubmit(canvas);

		await waitFor(() => expect(currentLocation()).toBe(`/catalogues/${CATALOGUE_UUID}`));
		await expect(posted).toEqual([{ title: 'Station kanji', type: 6, publicity: false, tags: '' }]);
	},
};

/** The POST never answers: the submit control is disabled, and the route stays where it is. */
export const Submitting: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getCatalogueStoreMockHandler(async () => {
				await delay('infinite');

				return { uuid: CATALOGUE_UUID };
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await fillAndSubmit(canvas);

		await waitFor(() => expect(canvas.getByRole('button', { name: 'Create catalogue' })).toBeDisabled());
		await expect(currentLocation()).toBe('/catalogues/new');
	},
};

/** A 422 puts each message under its field and moves focus to the first one. Nothing navigates. */
export const ServerValidation: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getCatalogueStoreMockHandler(() =>
				rejectWith({
					title: ['The list title must be at least 2 characters'],
					tags: ['The tags must be a string.'],
				}),
			),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await fillAndSubmit(canvas);

		await expect(await canvas.findByRole('alert')).toHaveTextContent('Check the highlighted fields and try again.');
		await waitFor(() => expect(canvas.getByLabelText('Title')).toHaveFocus());
		await expect(canvas.getByLabelText('Title')).toHaveAccessibleDescription(/at least 2 characters/);
		await expect(canvas.getByLabelText('Tags')).toHaveAccessibleDescription(/must be a string/);
		await expect(currentLocation()).toBe('/catalogues/new');
	},
};

/** A 500 shows the general error above the form, keeps what was typed and does not navigate. */
export const SubmitError: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getCatalogueStoreMockHandler(() => failWith(500)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await fillAndSubmit(canvas);

		await expect(await canvas.findByRole('alert')).toHaveTextContent('Something went wrong. Please try again.');
		await expect(canvas.getByLabelText('Title')).toHaveValue('Station kanji');
		await expect(canvas.getByRole('button', { name: 'Create catalogue' })).toBeEnabled();
		await expect(currentLocation()).toBe('/catalogues/new');
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'Create catalogue' })).toBeVisible();
	},
};
