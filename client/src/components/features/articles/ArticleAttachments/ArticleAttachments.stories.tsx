import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import {
	getCatalogueAddItemMockHandler,
	getCatalogueForItemMockHandler,
} from '@/api/generated/catalogue/catalogue.msw';
import { getKanjiIndexMockHandler } from '@/api/generated/kanji/kanji.msw';
import type { StoreCatalogueItemRequest } from '@/api/generated/model/storeCatalogueItemRequest';
import { getWordIndexMockHandler } from '@/api/generated/word/word.msw';
import { SavedListType } from '@/shared/constants/enums';
import { users } from '@/storybook/fixtures/users';
import { catalogueForItem } from '../../catalogues/fixtures';
import { kanjiIndexPage, wordIndexPage } from '../fixtures';
import { ArticleAttachments, PREVIEW_KANJI, PREVIEW_WORDS } from './';

const ARTICLE_UUID = 'a1a1a1a1-0000-4000-8000-000000000001';

/*
 * The kanji and words an article's processing attached, on the real hooks: both lists are the
 * ordinary kanji and word indexes filtered by `article_uuid`, answered by the Orval-generated MSW
 * handlers (#333) and overridden with the generated factories.
 */

/** What the generated POST handler received, newest last; cleared before each story. */
const added: { uuid: string; body: StoreCatalogueItemRequest }[] = [];

const neverAnswers = () => async () => {
	await delay('infinite');

	return { items: [], pagination: { page: 1, per_page: 20, total: 0, last_page: 1, has_more: false } };
};

const meta = {
	title: 'Features/Articles/ArticleAttachments',
	component: ArticleAttachments,
	parameters: { layout: 'padded' },
	args: { articleUuid: ARTICLE_UUID, showSave: false },
	decorators: [
		(Story) => (
			<div style={{ maxWidth: 720 }}>
				<Story />
			</div>
		),
	],
	beforeEach: ({ msw }) => {
		added.length = 0;

		msw.use(getKanjiIndexMockHandler(kanjiIndexPage(171)), getWordIndexMockHandler(wordIndexPage(233)));
	},
} satisfies Meta<typeof ArticleAttachments>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Five of each under the text, with the totals on the buttons that open the full lists. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByRole('button', { name: 'See all 171 kanji' })).toBeVisible();
		await expect(await canvas.findByRole('button', { name: 'See all 233 words' })).toBeVisible();
		await expect(canvas.getAllByRole('link')).toHaveLength(PREVIEW_KANJI + PREVIEW_WORDS);
		await expect(canvas.getByRole('heading', { level: 2, name: 'Kanji and words in this reading' })).toBeVisible();
		await expect(canvas.queryByRole('table')).toBeNull();
	},
};

/** Processing found nothing: each list says so plainly. */
export const Empty: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getKanjiIndexMockHandler(kanjiIndexPage(0)), getWordIndexMockHandler(wordIndexPage(0)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('No kanji have been attached to this article yet.')).toBeVisible();
		await expect(await canvas.findByText('No words have been attached to this article yet.')).toBeVisible();
		await expect(canvas.queryByRole('button', { name: /^See all/ })).toBeNull();
	},
};

/** While processing is still running, an empty list means "not yet", and says when to look again. */
export const EmptyWhileProcessing: Story = {
	args: { isProcessing: true },
	beforeEach: ({ msw }) => {
		msw.use(getKanjiIndexMockHandler(kanjiIndexPage(0)), getWordIndexMockHandler(wordIndexPage(0)));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(
			await canvas.findByText(
				'No kanji have been attached to this article yet. They appear here once processing finishes.',
			),
		).toBeVisible();
	},
};

/** Neither index has answered. */
export const Loading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(getKanjiIndexMockHandler(neverAnswers()), getWordIndexMockHandler(neverAnswers()));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByText('Loading kanji…')).toBeVisible();
		await expect(canvas.getByText('Loading words…')).toBeVisible();
	},
};

/** The kanji index fails with a 500: that list says so in its own words, and the words are unaffected. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(
			getKanjiIndexMockHandler(() => {
				throw new Response(null, { status: 500 });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(await canvas.findByRole('status')).toHaveTextContent(
			'Kanji could not be loaded. Reload the page to try again.',
		);
		await expect(await canvas.findByRole('button', { name: 'See all 233 words' })).toBeVisible();
	},
};

/** A guest reads both lists in full, with no Save column. */
export const Guest: Story = {
	parameters: { auth: 'guest' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(await canvas.findByRole('button', { name: 'See all 171 kanji' }));

		const dialog = within(await within(document.body).findByRole('dialog', { name: 'Kanji in this reading' }));
		await expect(await dialog.findByRole('table')).toBeInTheDocument();
		await expect(dialog.queryByRole('button', { name: /^Save / })).toBeNull();
	},
};

/**
 * A member opens the kanji list, goes to the second page and saves one kanji to a catalogue: the
 * generated POST handler receives the row's id, and the row reads as saved.
 */
export const Member: Story = {
	args: { showSave: true },
	parameters: { auth: { user: users.member } },
	beforeEach: ({ msw }) => {
		msw.use(
			getCatalogueForItemMockHandler({
				items: [
					catalogueForItem(1, { title: 'Station kanji', type: SavedListType.KANJIS, type_label: 'Kanji' }),
					catalogueForItem(2, { title: 'N3 kanji', type: SavedListType.KANJIS, type_label: 'Kanji' }),
				],
			}),
			getCatalogueAddItemMockHandler(async ({ request, params }) => {
				added.push({ uuid: String(params.uuid), body: (await request.json()) as StoreCatalogueItemRequest });

				return [];
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(await canvas.findByRole('button', { name: 'See all 171 kanji' }));

		const list = within(await within(document.body).findByRole('dialog', { name: 'Kanji in this reading' }));
		await expect(await list.findByText('Showing 1–20 of 171 kanji')).toBeInTheDocument();

		await userEvent.click(list.getByRole('button', { name: 'Next page' }));
		await expect(await list.findByText('Showing 21–40 of 171 kanji')).toBeInTheDocument();

		await userEvent.click((await list.findAllByRole('button', { name: /^Save / }))[0]);
		const chooser = within(await list.findByRole('dialog', { name: 'Choose Kanji List to add' }));
		await userEvent.click((await chooser.findAllByRole('button', { name: 'Add' }))[0]);

		await waitFor(() => expect(chooser.getAllByRole('button', { name: 'Remove' })).toHaveLength(1));
		await expect(added).toHaveLength(1);
		await expect(added[0].body).toEqual({ item_id: 10_020 });
	},
};
