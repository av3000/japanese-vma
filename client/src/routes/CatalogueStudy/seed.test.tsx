// @vitest-environment jsdom
import type { ReactNode } from 'react';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useCatalogueQuery } from '@/api/catalogues/details';
import { useStudyDeck } from '@/api/flashcards/deck';
import { renderWithAct } from '@/test/renderWithAct';
import CatalogueStudyPage from './index';

/**
 * The seed round-trip runs in an effect, which the static-markup tests in index.test.tsx
 * never execute; this file mounts the route for real.
 */

let searchParams = new URLSearchParams();
const setSearchParamsMock = vi.fn();
const recorderStartMock = vi.fn();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		useParams: () => ({ catalogueId: 'd453be67-1519-43e2-94ab-af85b79aeb31' }),
		useSearchParams: () => [searchParams, setSearchParamsMock],
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
	};
});

vi.mock('@/api/catalogues/details', () => ({ useCatalogueQuery: vi.fn() }));

vi.mock('@/api/flashcards/deck', async () => {
	const actual = await vi.importActual<typeof import('@/api/flashcards/deck')>('@/api/flashcards/deck');
	return { ...actual, useStudyDeck: vi.fn() };
});

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({ isAuthenticated: true, isLoading: false, user: { id: 1 } }),
}));

vi.mock('@/api/flashcards/sessions', () => ({
	useSessionRecorder: () => ({
		status: 'recording',
		start: recorderStartMock,
		recordAttempt: vi.fn(),
		complete: vi.fn(),
	}),
}));

vi.mock('@/components/features/flashcards/StudySession', () => ({
	StudySession: () => <div>Session</div>,
}));

vi.mock('@/components/shared/Icon', () => ({ Icon: () => null }));

const deck = (seed: number, cards = 3) => ({
	data: {
		catalogue: { uuid: 'c', title: 'N5 kanji', type: 6, type_label: 'Kanji' },
		config: { prompt: 'character', answer: 'meaning', mode: 'options', script: 'strict', count: 20, seed },
		cards: Array.from({ length: cards }, (_, index) => ({ itemId: index + 1 })),
		totalItems: 3,
		eligibleItems: 3,
		excludedEmptyAnswerField: 0,
	},
	isPending: false,
	isError: false,
	error: null,
});

describe('CatalogueStudyPage seed pinning', () => {
	let unmount: (() => Promise<void>) | undefined;

	beforeEach(() => {
		vi.clearAllMocks();
		vi.mocked(useCatalogueQuery).mockReturnValue({
			data: { id: 55, uuid: 'c', type: 6, type_label: 'Kanji', title: 'N5 kanji', items_count: 3 },
			isPending: false,
			isError: false,
		} as never);
	});

	afterEach(async () => {
		await unmount?.();
		unmount = undefined;
	});

	it('writes the seed the first deck chose into the URL, replacing the entry', async () => {
		searchParams = new URLSearchParams('prompt=character&answer=meaning');
		vi.mocked(useStudyDeck).mockReturnValue(deck(4242) as never);

		const rendered = await renderWithAct(<CatalogueStudyPage />);
		unmount = rendered.unmount;

		expect(setSearchParamsMock).toHaveBeenCalledTimes(1);
		const [params, options] = setSearchParamsMock.mock.calls[0];
		expect((params as URLSearchParams).get('seed')).toBe('4242');
		expect((params as URLSearchParams).get('play')).toBeNull();
		expect(options).toEqual({ replace: true });
	});

	it('leaves a URL that already carries a seed alone', async () => {
		searchParams = new URLSearchParams('prompt=character&answer=meaning&seed=9');
		vi.mocked(useStudyDeck).mockReturnValue(deck(9) as never);

		const rendered = await renderWithAct(<CatalogueStudyPage />);
		unmount = rendered.unmount;

		expect(setSearchParamsMock).not.toHaveBeenCalled();
	});

	it('keeps play=1 when pinning the seed mid-run and starts the saved session once', async () => {
		searchParams = new URLSearchParams('prompt=character&answer=meaning&play=1');
		vi.mocked(useStudyDeck).mockReturnValue(deck(77) as never);

		const rendered = await renderWithAct(<CatalogueStudyPage />);
		unmount = rendered.unmount;

		const [params] = setSearchParamsMock.mock.calls[0];
		expect((params as URLSearchParams).get('seed')).toBe('77');
		expect((params as URLSearchParams).get('play')).toBe('1');
		expect(recorderStartMock).toHaveBeenCalledTimes(1);
		expect(recorderStartMock).toHaveBeenCalledWith(
			'd453be67-1519-43e2-94ab-af85b79aeb31',
			expect.objectContaining({ seed: 77 }),
			3,
		);
	});
});
