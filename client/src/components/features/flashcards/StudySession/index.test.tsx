// @vitest-environment jsdom
import type { ReactNode } from 'react';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { click, submitForm, typeInto } from '@/test/formEvents';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import { kanjiOptionsDeck, kanjiTypedKunyomiDeck } from '../fixtures';
import { StudySession } from './index';

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
	};
});

const buttonNamed = (root: ParentNode, text: string): HTMLButtonElement => {
	const button = Array.from(root.querySelectorAll('button')).find((element) => element.textContent?.includes(text));
	if (!button) throw new Error(`Expected a button containing "${text}".`);
	return button;
};

describe('StudySession', () => {
	let unmount: (() => Promise<void>) | undefined;

	afterEach(async () => {
		await unmount?.();
		unmount = undefined;
	});

	it('runs an options deck from the first card to the summary', async () => {
		const onComplete = vi.fn();
		const rendered = await renderWithAct(
			<StudySession
				deck={kanjiOptionsDeck}
				catalogueHref="/catalogues/c-1"
				onChangeSetup={vi.fn()}
				onComplete={onComplete}
			/>,
		);
		unmount = rendered.unmount;
		const { container } = rendered;

		expect(container.textContent).toContain('学');
		expect(container.querySelector('[role="progressbar"]')?.getAttribute('aria-valuenow')).toBe('1');

		// Card 1: correct.
		click(buttonNamed(container, 'study'));
		expect(container.textContent).toContain('Correct');
		click(buttonNamed(container, 'Next card'));

		// Card 2: wrong.
		expect(container.textContent).toContain('水');
		click(buttonNamed(container, 'fire'));
		expect(container.textContent).toContain('Not quite');
		expect(container.textContent).toContain('you answered');
		click(buttonNamed(container, 'Next card'));

		// Card 3: correct, last.
		expect(container.textContent).toContain('一');
		expect(container.textContent).toContain('1 correct');
		click(buttonNamed(container, 'one'));
		click(buttonNamed(container, 'See results'));

		expect(container.textContent).toContain('2 of 3 correct');
		expect(container.textContent).toContain('1 card to look at again');
		expect(container.querySelector('[aria-label="Missed cards"]')?.textContent).toContain('水');
		expect(onComplete).toHaveBeenCalledTimes(1);
		expect(onComplete.mock.calls[0][0]).toHaveLength(3);
		expect(onComplete.mock.calls[0][0].map((answer: { correct: boolean }) => answer.correct)).toEqual([
			true,
			false,
			true,
		]);
	});

	it('grades typed answers through the grading rules', async () => {
		const rendered = await renderWithAct(
			<StudySession deck={kanjiTypedKunyomiDeck} catalogueHref="/catalogues/c-1" onChangeSetup={vi.fn()} />,
		);
		unmount = rendered.unmount;
		const { container } = rendered;

		// The okurigana dot in まな.ぶ is not typed by the learner.
		typeInto(requireElement<HTMLInputElement>(container, 'input'), 'まなぶ');
		submitForm(requireElement<HTMLFormElement>(container, 'form'));

		expect(container.textContent).toContain('Correct');
		expect(container.textContent).toContain('まな.ぶ');
		click(buttonNamed(container, 'Next card'));

		typeInto(requireElement<HTMLInputElement>(container, 'input'), 'ミズ');
		submitForm(requireElement<HTMLFormElement>(container, 'form'));

		// Strict script: katakana is not a kun'yomi.
		expect(container.textContent).toContain('Not quite');
		click(buttonNamed(container, 'See results'));

		expect(container.textContent).toContain('1 of 2 correct');
	});

	it('restarts from the first card with a clean score', async () => {
		const rendered = await renderWithAct(
			<StudySession
				deck={{ ...kanjiOptionsDeck, cards: [kanjiOptionsDeck.cards[0]] }}
				catalogueHref="/c"
				onChangeSetup={vi.fn()}
			/>,
		);
		unmount = rendered.unmount;
		const { container } = rendered;

		click(buttonNamed(container, 'fire'));
		click(buttonNamed(container, 'See results'));
		expect(container.textContent).toContain('0 of 1 correct');

		click(buttonNamed(container, 'Study again'));

		expect(container.textContent).toContain('学');
		expect(container.textContent).toContain('0 correct');
		expect(container.querySelector('[role="progressbar"]')).not.toBeNull();
	});
});
