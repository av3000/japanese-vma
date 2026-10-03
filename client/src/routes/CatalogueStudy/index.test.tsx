import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useCatalogueQuery } from '@/api/catalogues/details';
import { useStudyDeck } from '@/api/flashcards/deck';
import CatalogueStudyPage from './index';

const useParamsMock = vi.fn();
let searchParams = new URLSearchParams();
const setSearchParamsMock = vi.fn();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		useParams: () => useParamsMock(),
		useSearchParams: () => [searchParams, setSearchParamsMock],
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
	};
});

vi.mock('@/api/catalogues/details', () => ({
	useCatalogueQuery: vi.fn(),
}));

vi.mock('@/api/flashcards/deck', async () => {
	const actual = await vi.importActual<typeof import('@/api/flashcards/deck')>('@/api/flashcards/deck');
	return {
		...actual,
		useStudyDeck: vi.fn(),
	};
});

vi.mock('@/components/features/flashcards/StudySession', () => ({
	StudySession: ({ deck, saveStatus }: { deck: { cards: unknown[] }; saveStatus: string }) => (
		<div>
			Session with {deck.cards.length} cards ({saveStatus})
		</div>
	),
}));

let isAuthenticatedMock = false;

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({ isAuthenticated: isAuthenticatedMock, isLoading: false, user: null }),
}));

const recorderStartMock = vi.fn();

vi.mock('@/api/flashcards/sessions', () => ({
	useSessionRecorder: (enabled: boolean) => ({
		status: enabled ? 'recording' : 'disabled',
		start: recorderStartMock,
		recordAttempt: vi.fn(),
		complete: vi.fn(),
	}),
}));

vi.mock('@/components/shared/Icon', () => ({
	Icon: ({ name }: { name: string }) => <span>{name}</span>,
}));

const catalogue = (type: number) => ({
	id: 55,
	uuid: 'd453be67-1519-43e2-94ab-af85b79aeb31',
	type,
	type_label: type === 8 ? 'Sentences' : 'Kanji',
	title: 'N5 kanji',
	items_count: 12,
});

const loadedCatalogue = (type = 6) =>
	vi.mocked(useCatalogueQuery).mockReturnValue({ data: catalogue(type), isPending: false, isError: false } as never);

const readyDeck = (cards = 10) =>
	vi.mocked(useStudyDeck).mockReturnValue({
		data: {
			config: { seed: 7 },
			cards: Array.from({ length: cards }, (_, index) => ({ itemId: index + 1 })),
			totalItems: 12,
			eligibleItems: 10,
			excludedEmptyAnswerField: 2,
		},
		isPending: false,
		isError: false,
		error: null,
	} as never);

describe('CatalogueStudyPage', () => {
	beforeEach(() => {
		vi.clearAllMocks();
		isAuthenticatedMock = false;
		searchParams = new URLSearchParams();
		useParamsMock.mockReturnValue({ catalogueId: 'd453be67-1519-43e2-94ab-af85b79aeb31' });
	});

	it('renders the form loading family while the catalogue is pending', () => {
		vi.mocked(useCatalogueQuery).mockReturnValue({ data: undefined, isPending: true, isError: false } as never);
		vi.mocked(useStudyDeck).mockReturnValue({ data: undefined, isPending: true, isError: false } as never);

		const html = renderToStaticMarkup(<CatalogueStudyPage />);

		expect(html).toContain('data-loading-family="form"');
	});

	it('shows the setup form with the deck preview for a supported catalogue', () => {
		loadedCatalogue();
		readyDeck();

		const html = renderToStaticMarkup(<CatalogueStudyPage />);

		expect(html).toContain('Study: N5 kanji');
		expect(html).toContain('Back to N5 kanji');
		expect(html).toContain('<strong>10</strong> of 12 items can be asked this way');
		// The preview asks for one card in the cheapest mode; the real deck waits for Start.
		expect(useStudyDeck).toHaveBeenCalledWith(
			'd453be67-1519-43e2-94ab-af85b79aeb31',
			expect.objectContaining({ prompt: 'character', answer: 'meaning', mode: 'typed', count: 1 }),
			true,
		);
		expect(useStudyDeck).toHaveBeenCalledWith(
			'd453be67-1519-43e2-94ab-af85b79aeb31',
			expect.objectContaining({ prompt: 'character', answer: 'meaning', mode: 'options', count: 20 }),
			false,
		);
	});

	it('reads the configuration from the URL and drops a combination the type does not allow', () => {
		loadedCatalogue();
		readyDeck();
		searchParams = new URLSearchParams('prompt=character&answer=reading&mode=typed&count=5&seed=3');

		renderToStaticMarkup(<CatalogueStudyPage />);

		expect(useStudyDeck).toHaveBeenCalledWith(
			expect.any(String),
			expect.objectContaining({ prompt: 'character', answer: 'meaning', mode: 'options', count: 5, seed: 3 }),
			false,
		);
	});

	it('does not request a deck for a sentences catalogue and says why', () => {
		loadedCatalogue(8);
		vi.mocked(useStudyDeck).mockReturnValue({ data: undefined, isPending: false, isError: false } as never);

		const html = renderToStaticMarkup(<CatalogueStudyPage />);

		expect(html).toContain('This catalogue cannot be studied');
		expect(useStudyDeck).not.toHaveBeenCalledWith(expect.any(String), expect.anything(), true);
	});

	it('renders the backend refusal as an alert instead of crashing', () => {
		loadedCatalogue();
		vi.mocked(useStudyDeck).mockReturnValue({
			data: undefined,
			isPending: false,
			isError: true,
			error: { response: { status: 422, data: { title: 'No eligible cards', detail: 'No item has a kunyomi' } } },
		} as never);

		const html = renderToStaticMarkup(<CatalogueStudyPage />);

		expect(html).toContain('No eligible cards');
		expect(html).toContain('No item has a kunyomi');
	});

	it('switches to the session when play=1 and the deck is loaded', () => {
		loadedCatalogue();
		readyDeck(7);
		searchParams = new URLSearchParams('play=1');

		const html = renderToStaticMarkup(<CatalogueStudyPage />);

		expect(html).toContain('Session with 7 cards (disabled)');
		expect(html).not.toContain('Start studying');
	});

	it('hands a signed-in learner a recording session', () => {
		loadedCatalogue();
		readyDeck(3);
		searchParams = new URLSearchParams('play=1');
		isAuthenticatedMock = true;

		const html = renderToStaticMarkup(<CatalogueStudyPage />);

		expect(html).toContain('Session with 3 cards (recording)');
	});

	it('shows the not-found state when the catalogue cannot be loaded', () => {
		vi.mocked(useCatalogueQuery).mockReturnValue({ data: undefined, isPending: false, isError: true } as never);
		vi.mocked(useStudyDeck).mockReturnValue({ data: undefined, isPending: false, isError: false } as never);

		const html = renderToStaticMarkup(<CatalogueStudyPage />);

		expect(html).toContain('Catalogue not found');
	});
});
