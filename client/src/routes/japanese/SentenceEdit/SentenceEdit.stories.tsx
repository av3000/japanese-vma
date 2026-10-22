import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import type { UpdateSentenceRequest } from '@/api/generated/model/updateSentenceRequest';
import { getSentenceShowMockHandler, getSentenceUpdateMockHandler } from '@/api/generated/sentence/sentence.msw';
import { sentenceShort } from '@/components/features/japanese/related/dictionaryDetail.fixtures';
import { currentLocation } from '@/storybook/decorators';
import { users } from '@/storybook/fixtures/users';
import { SENTENCE_UUID, failWith, ownSentence } from '../../routeFixtures';
import SentenceEditPage from './';

/*
 * The Edit sentence route on its real hooks: the generated show handler loads the sentence and the
 * generated PUT handler saves it. The story asserts the body the handler received and where the
 * route navigated afterwards.
 */

const EDIT_PATH = `/sentences/${SENTENCE_UUID}/edit`;

const meta = {
	title: 'Pages/SentenceEdit',
	component: SentenceEditPage,
	parameters: {
		layout: 'fullscreen',
		auth: 'member',
		router: { path: '/sentences/:sentence_id/edit', initialEntries: [EDIT_PATH] },
	},
	beforeEach: ({ msw }) => {
		msw.use(getSentenceShowMockHandler(ownSentence()));
	},
} satisfies Meta<typeof SentenceEditPage>;

export default meta;

type Story = StoryObj<typeof meta>;

/** What the generated PUT handler received, newest last; cleared before each story that saves. */
const saved: UpdateSentenceRequest[] = [];

const changeAndSave = async (canvas: ReturnType<typeof within>) => {
	const field = await canvas.findByLabelText('Sentence');
	await userEvent.clear(field);
	await userEvent.type(field, '親友は何人いますか？');
	await userEvent.click(canvas.getByRole('button', { name: 'Save changes' }));
};

/** The member's own sentence opens in the form; saving sends the new text and opens the sentence. */
export const Edit: Story = {
	beforeEach: ({ msw }) => {
		saved.length = 0;

		msw.use(
			getSentenceUpdateMockHandler(async ({ request }) => {
				const body = (await request.json()) as UpdateSentenceRequest;
				saved.push(body);

				return ownSentence({ content: body.content });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByLabelText('Sentence')).toHaveValue('親友は何人いるの？');
		await expect(canvas.getByRole('heading', { level: 1, name: 'Edit sentence' })).toBeVisible();

		await changeAndSave(canvas);

		await waitFor(() => expect(currentLocation()).toBe(`/sentence/${SENTENCE_UUID}`));
		await expect(saved).toEqual([{ content: '親友は何人いますか？' }]);
	},
};

/** A 404: the route says the sentence could not be loaded, with the way back. */
export const NotFound: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getSentenceShowMockHandler(() => failWith(404)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('Sentence could not be loaded.')).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Sentences' })).toHaveAttribute('href', '/sentences');
	},
};

/** Another member opens the edit address: the route refuses before showing the form. */
export const NotOwner: Story = {
	parameters: { auth: { user: users.other } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('You do not have permission to edit this sentence.')).toBeVisible();
		await expect(canvas.queryByLabelText('Sentence')).toBeNull();
	},
};

/** A Tatoeba sentence has no author, so nobody can edit it, not even its would-be owner. */
export const Imported: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getSentenceShowMockHandler({ ...sentenceShort, uuid: SENTENCE_UUID }));
	},
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByText('Imported sentences cannot be edited.')).toBeVisible();
	},
};
