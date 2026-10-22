import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import type { StudyCard } from '@/api/flashcards/deck';
import { getCatalogueShowMockHandler } from '@/api/generated/catalogue/catalogue.msw';
import { getFlashcardDeckShowMockHandler } from '@/api/generated/flashcard-deck/flashcard-deck.msw';
import type {
	CatalogueDetailResource,
	CompleteStudySessionRequest,
	FlashcardDeckResource,
	FlashcardResource,
	StoreStudyAttemptRequest,
	StoreStudySessionRequest,
} from '@/api/generated/model';
import {
	getStudySessionCompleteMockHandler,
	getStudySessionStoreAttemptMockHandler,
	getStudySessionStoreMockHandler,
} from '@/api/generated/study-session/study-session.msw';
import { kanjiCards, kanjiTypedKunyomiDeck } from '@/components/features/flashcards/fixtures';
import { currentLocation } from '@/storybook/decorators';
import { users } from '@/storybook/fixtures/users';
import { createRequestLog } from '../pageFixtures';
import CatalogueStudy from './';

/*
 * `/catalogues/:catalogueId/study` on its real route component and hooks: the catalogue and the
 * deck come through the generated MSW handlers, and a signed-in run saves itself through the
 * session handlers. The configuration, seed included, lives in the URL; `play=1` switches from the
 * setup form to the session.
 */

const CATALOGUE_UUID = 'd453be67-1519-43e2-94ab-af85b79aeb31';
const STUDY_PATH = '/catalogues/:catalogueId/study';
const study = (search = '') => `/catalogues/${CATALOGUE_UUID}/study${search}`;

/** A run of the default setup: pick one of four, `count` cards, seed pinned as the route would. */
const PLAY = (count: number) =>
	`?prompt=character&answer=meaning&mode=options&script=strict&count=${count}&seed=7&play=1`;

const catalogue = (overrides: Partial<CatalogueDetailResource> = {}): CatalogueDetailResource => ({
	id: 55,
	uuid: CATALOGUE_UUID,
	type: 6,
	type_label: 'Kanji',
	title: 'N5 kanji',
	description: null,
	publicity: 1,
	owner: { id: 7, uuid: 'owner-uuid', name: 'Aki Tanaka' },
	items_count: 48,
	hashtags: [],
	engagement: { likes_count: 4, views_count: 128, downloads_count: 2, comments_count: 0, is_liked_by_viewer: false },
	jlpt_levels: null,
	items: [],
	created_at: '2026-04-01T12:00:00+00:00',
	updated_at: '2026-04-02T12:00:00+00:00',
	...overrides,
});

const typedCards: StudyCard[] = kanjiTypedKunyomiDeck.cards;

const toFlashcard = (card: StudyCard, withOptions: boolean): FlashcardResource => ({
	item_id: card.itemId,
	item_uuid: card.itemUuid,
	prompt: { text: card.promptText, hint: card.promptHint },
	accepted_answers: card.acceptedAnswers,
	display_answer: card.displayAnswer,
	options: withOptions ? card.options : null,
	meta: { jlpt: card.jlpt, grade: card.grade, strokes: card.strokes },
});

/** The deck the backend would build for the request's configuration: the first `count` fixture cards. */
const deckFor = (request: Request): FlashcardDeckResource => {
	const params = new URL(request.url).searchParams;
	const prompt = (params.get('prompt') ?? 'character') as FlashcardDeckResource['config']['prompt'];
	const answer = (params.get('answer') ?? 'meaning') as FlashcardDeckResource['config']['answer'];
	const mode = (params.get('mode') ?? 'options') as FlashcardDeckResource['config']['mode'];
	const script = (params.get('script') ?? 'strict') as FlashcardDeckResource['config']['script'];
	const count = Number(params.get('count') ?? 20);
	const source = answer === 'kunyomi' ? typedCards : kanjiCards;

	return {
		catalogue: { uuid: CATALOGUE_UUID, title: 'N5 kanji', type: 6, type_label: 'Kanji' },
		config: { prompt, answer, mode, script, count, seed: Number(params.get('seed') ?? 7) },
		cards: source.slice(0, count).map((card) => toFlashcard(card, mode === 'options')),
		total_items: 48,
		eligible_items: 45,
		excluded: { empty_answer_field: 3 },
	};
};

const searchOf = (location: string) => new URLSearchParams(location.split('?')[1]);

const deckRequests = createRequestLog();
const storedSessions: StoreStudySessionRequest[] = [];
const storedAttempts: StoreStudyAttemptRequest[] = [];
const completedSessions: CompleteStudySessionRequest[] = [];

/** Everything the page asks for, answered, and what a saved run sends recorded. */
const handlers = () => [
	getCatalogueShowMockHandler(catalogue()),
	getFlashcardDeckShowMockHandler(({ request }) => {
		deckRequests.record(request);

		return deckFor(request);
	}),
	getStudySessionStoreMockHandler(async ({ request }) => {
		storedSessions.push((await request.json()) as StoreStudySessionRequest);

		return { uuid: 'session-1' };
	}),
	getStudySessionStoreAttemptMockHandler(async ({ request }) => {
		storedAttempts.push((await request.json()) as StoreStudyAttemptRequest);

		return { success: true };
	}),
	getStudySessionCompleteMockHandler(async ({ request }) => {
		const body = (await request.json()) as CompleteStudySessionRequest;
		completedSessions.push(body);

		return {
			uuid: 'session-1',
			catalogue_uuid: CATALOGUE_UUID,
			catalogue_type: 6,
			config: { prompt: 'character', answer: 'meaning', mode: 'options', script: 'strict' },
			card_count: 1,
			correct_count: body.correct_count,
			started_at: '2026-10-13T09:00:00+00:00',
			completed_at: '2026-10-13T09:01:00+00:00',
		};
	}),
];

/** A 422 the way the API sends a refusal that is not a field-validation error. */
const refusal = () =>
	new Response(JSON.stringify({ title: 'Unprocessable Content', status: 422 }), {
		status: 422,
		headers: { 'Content-Type': 'application/problem+json' },
	});

const meta = {
	title: 'Pages/CatalogueStudy',
	component: CatalogueStudy,
	parameters: {
		layout: 'fullscreen',
		router: { path: STUDY_PATH, initialEntries: [study()] },
	},
	beforeEach: ({ msw }) => {
		deckRequests.clear();
		storedSessions.length = 0;
		storedAttempts.length = 0;
		completedSessions.length = 0;
		msw.use(...handlers());
	},
} satisfies Meta<typeof CatalogueStudy>;

export default meta;

type Story = StoryObj<typeof meta>;

/** The setup form with the deck preview: 45 of 48 items can be asked, three are skipped. */
export const Setup: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByRole('heading', { level: 1, name: 'Study: N5 kanji' })).toBeVisible();
		await expect(canvas.getByText('Kanji · 48 items')).toBeVisible();
		await expect(canvas.getByRole('link', { name: /Back to N5 kanji/ })).toHaveAttribute(
			'href',
			`/catalogues/${CATALOGUE_UUID}`,
		);
		await expect(canvas.getByLabelText('Card shows')).toHaveValue('character');
		await expect(canvas.getByLabelText('You answer with')).toHaveValue('meaning');

		// The first deck's seed is pinned in the URL, which asks for the preview once more.
		await waitFor(() => expect(searchOf(currentLocation()).get('seed')).toBe('7'));
		await expect(await canvas.findByText(/3 have nothing to answer with and will be skipped/)).toBeVisible();
		await expect(canvas.getByText(/of 48 items can be asked this way/)).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Start studying' })).toBeEnabled();

		// The preview asks for one card in the cheapest mode; the real deck waits for Start.
		await expect(deckRequests.searches[0].get('count')).toBe('1');
		await expect(deckRequests.searches[0].get('mode')).toBe('typed');
	},
};

/** Three cards, four options each: the first card, and the progress line. */
export const InSession: Story = {
	parameters: { router: { path: STUDY_PATH, initialEntries: [study(PLAY(3))] } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		const session = within(await canvas.findByRole('region', { name: 'Study session' }));
		await expect(session.getByText('学')).toBeVisible();
		await expect(
			within(session.getByRole('list', { name: 'Answer options' })).getAllByRole('listitem'),
		).toHaveLength(4);

		await expect(deckRequests.last()).toMatchObject({ count: '3', mode: 'options', seed: '7' });
	},
};

/** A guest plays the whole run, anonymously: the summary offers sign-in, and nothing is sent to be saved. */
export const Guest: Story = {
	parameters: { auth: 'guest', router: { path: STUDY_PATH, initialEntries: [study(PLAY(1))] } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await userEvent.click(await canvas.findByRole('button', { name: 'study' }));
		await userEvent.click(await canvas.findByRole('button', { name: 'See results' }));

		await expect(await canvas.findByRole('heading', { level: 2, name: '1 of 1 correct' })).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Sign in' })).toHaveAttribute('href', '/login');
		await expect(canvas.getByText(/to save your progress/)).toBeVisible();

		await expect(storedSessions).toHaveLength(0);
		await expect(storedAttempts).toHaveLength(0);
		await expect(completedSessions).toHaveLength(0);
	},
};

/** A member's run saves itself: the session, each attempt and the score reach the generated session handlers. */
export const Member: Story = {
	parameters: { auth: { user: users.member }, router: { path: STUDY_PATH, initialEntries: [study(PLAY(1))] } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await userEvent.click(await canvas.findByRole('button', { name: 'study' }));
		await userEvent.click(await canvas.findByRole('button', { name: 'See results' }));

		await expect(await canvas.findByText('Results saved.')).toBeVisible();
		await expect(canvas.queryByRole('link', { name: 'Sign in' })).not.toBeInTheDocument();

		await expect(storedSessions).toEqual([
			{
				catalogue_uuid: CATALOGUE_UUID,
				prompt: 'character',
				answer: 'meaning',
				mode: 'options',
				script: 'strict',
				card_count: 1,
			},
		]);
		await expect(storedAttempts).toHaveLength(1);
		await expect(storedAttempts[0]).toMatchObject({
			item_id: 1,
			attempt_no: 1,
			given_answer: 'study',
			is_correct: true,
		});
		await expect(completedSessions).toEqual([{ correct_count: 1 }]);
	},
};

/** Setup, Start, three answers (one wrong) and the summary, all through the handlers. */
export const WalksSetupToSummary: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		// The first deck's seed is pinned in the URL, which asks for the preview once more; Start waits for it.
		await waitFor(() => expect(searchOf(currentLocation()).get('seed')).toBe('7'));
		await canvas.findByText(/items can be asked this way/);
		await waitFor(() => expect(canvas.getByRole('button', { name: 'Start studying' })).toBeEnabled());
		await userEvent.click(canvas.getByRole('button', { name: 'Start studying' }));

		await waitFor(() => expect(searchOf(currentLocation()).get('play')).toBe('1'));
		const session = within(await canvas.findByRole('region', { name: 'Study session' }));
		await expect(session.getByText('学')).toBeVisible();
		await expect(deckRequests.last()).toMatchObject({ mode: 'options', count: '20' });

		// 学 is "study": right.
		await userEvent.click(session.getByRole('button', { name: 'study' }));
		await expect(await canvas.findByRole('status')).toHaveTextContent('Correct');
		await userEvent.click(canvas.getByRole('button', { name: 'Next card' }));

		// 水 is "water": wrong.
		await expect(await canvas.findByText('水')).toBeVisible();
		await userEvent.click(canvas.getByRole('button', { name: 'fire' }));
		await expect(await canvas.findByRole('status')).toHaveTextContent('Not quite');
		await userEvent.click(canvas.getByRole('button', { name: 'Next card' }));

		// 一 is "one": right.
		await expect(await canvas.findByText('一')).toBeVisible();
		await userEvent.click(canvas.getByRole('button', { name: 'one' }));
		await userEvent.click(await canvas.findByRole('button', { name: 'See results' }));

		await expect(await canvas.findByRole('heading', { level: 2, name: '2 of 3 correct' })).toBeVisible();
		await expect(within(canvas.getByRole('list', { name: 'Missed cards' })).getByText('水')).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Retry missed (1)' })).toBeVisible();

		// Change setup goes back to the form, with the same configuration.
		await userEvent.click(canvas.getByRole('button', { name: 'Change setup' }));
		await expect(await canvas.findByRole('button', { name: 'Start studying' })).toBeVisible();
		await expect(searchOf(currentLocation()).get('play')).toBeNull();
	},
};

/** The end of a run, reached by answering both cards of a two-card deck: one right, one wrong. */
export const Summary: Story = {
	parameters: { router: { path: STUDY_PATH, initialEntries: [study(PLAY(2))] } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await userEvent.click(await canvas.findByRole('button', { name: 'study' }));
		await userEvent.click(await canvas.findByRole('button', { name: 'Next card' }));
		await userEvent.click(await canvas.findByRole('button', { name: 'fire' }));
		await userEvent.click(await canvas.findByRole('button', { name: 'See results' }));

		await expect(await canvas.findByRole('heading', { level: 2, name: '1 of 2 correct' })).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Study again' })).toBeVisible();
		// The header's back link and the summary's own.
		await expect(canvas.getAllByRole('link', { name: 'Back to N5 kanji' })).toHaveLength(2);
	},
};

/** A catalogue with no items: the backend refuses the deck, and the form says so and cannot start. */
export const EmptyCatalogue: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getCatalogueShowMockHandler(catalogue({ items_count: 0 })),
			getFlashcardDeckShowMockHandler(() => {
				throw refusal();
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('Kanji · 0 items')).toBeVisible();
		await expect(await canvas.findByText('No cards to study this way')).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Start studying' })).toBeDisabled();
	},
};

/** An unknown or deleted catalogue: a plain message and the way back, no form. */
export const NotFound: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getCatalogueShowMockHandler(() => {
				throw new Response(null, { status: 404 });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('Catalogue not found or was deleted.')).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'Back to all Catalogues' })).toHaveAttribute(
			'href',
			'/catalogues',
		);
		await expect(canvas.queryByRole('button', { name: 'Start studying' })).not.toBeInTheDocument();
		await expect(deckRequests.searches).toHaveLength(0);
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByText(/items can be asked this way/)).toBeVisible();
	},
};
