import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import type { StoreSentenceRequest } from '@/api/generated/model/storeSentenceRequest';
import { getSentenceStoreMockHandler } from '@/api/generated/sentence/sentence.msw';
import { currentLocation } from '@/storybook/decorators';
import { SENTENCE_UUID, failWith, ownSentence, rejectWith } from '../../routeFixtures';
import SentenceCreatePage from './';

/*
 * The New sentence route on its real mutation: the generated POST handler answers, and the story
 * asserts the body it received and where the route navigated afterwards. The route only has a
 * create form, so there is no entity to load: no loading, not-found or not-owner state exists.
 */

const meta = {
	title: 'Pages/SentenceCreate',
	component: SentenceCreatePage,
	parameters: {
		layout: 'fullscreen',
		auth: 'member',
		router: { path: '/sentences/new', initialEntries: ['/sentences/new'] },
	},
} satisfies Meta<typeof SentenceCreatePage>;

export default meta;

type Story = StoryObj<typeof meta>;

/** What the generated POST handler received, newest last; cleared before each story that posts. */
const posted: StoreSentenceRequest[] = [];

const typeAndSubmit = async (canvas: ReturnType<typeof within>) => {
	await userEvent.type(canvas.getByLabelText('Sentence'), '  親友は何人いるの？ ');
	await userEvent.click(canvas.getByRole('button', { name: 'Create sentence' }));
};

/** Type a sentence and submit: the body is trimmed, and the route opens the new sentence. */
export const Create: Story = {
	beforeEach: ({ msw }) => {
		posted.length = 0;

		msw.use(
			getSentenceStoreMockHandler(async ({ request }) => {
				posted.push((await request.json()) as StoreSentenceRequest);

				return ownSentence();
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 1, name: 'New sentence' })).toBeVisible();

		await typeAndSubmit(canvas);

		await waitFor(() => expect(currentLocation()).toBe(`/sentence/${SENTENCE_UUID}`));
		await expect(posted).toEqual([{ content: '親友は何人いるの？' }]);
	},
};

/** The POST never answers: the submit control is disabled, and the route stays where it is. */
export const Submitting: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getSentenceStoreMockHandler(async () => {
				await delay('infinite');

				return ownSentence();
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await typeAndSubmit(canvas);

		await waitFor(() => expect(canvas.getByRole('button', { name: 'Create sentence' })).toBeDisabled());
		await expect(currentLocation()).toBe('/sentences/new');
	},
};

/** A 422 puts the message under the field and moves focus to it. Nothing navigates. */
export const ServerValidation: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getSentenceStoreMockHandler(() => rejectWith({ content: ['The content has already been taken.'] })));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await typeAndSubmit(canvas);

		await expect(await canvas.findByRole('alert')).toHaveTextContent('Check the highlighted fields and try again.');
		await waitFor(() => expect(canvas.getByLabelText('Sentence')).toHaveFocus());
		await expect(canvas.getByLabelText('Sentence')).toHaveAccessibleDescription(/already been taken/);
		await expect(currentLocation()).toBe('/sentences/new');
	},
};

/** A 500 shows the general error above the form, keeps what was typed and does not navigate. */
export const SubmitError: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getSentenceStoreMockHandler(() => failWith(500)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await typeAndSubmit(canvas);

		await expect(await canvas.findByRole('alert')).toHaveTextContent('Something went wrong. Please try again.');
		await expect(canvas.getByLabelText('Sentence')).toHaveValue('  親友は何人いるの？ ');
		await expect(currentLocation()).toBe('/sentences/new');
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'Create sentence' })).toBeVisible();
	},
};
