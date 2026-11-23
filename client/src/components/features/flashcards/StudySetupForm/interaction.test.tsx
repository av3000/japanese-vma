// @vitest-environment jsdom
import { afterEach, describe, expect, it, vi } from 'vitest';
import { defaultStudyConfig, type StudyConfig } from '@/api/flashcards/deck';
import { SavedListType } from '@/shared/constants/enums';
import { choose, controlLabelled, typeInto } from '@/test/formEvents';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import { StudySetupForm, type StudyDeckStatus } from './index';

const ready: StudyDeckStatus = { kind: 'ready', totalItems: 12, eligibleItems: 10, excludedEmptyAnswerField: 2 };

const lastConfig = (onChange: ReturnType<typeof vi.fn>): StudyConfig => onChange.mock.calls.at(-1)?.[0];

describe('StudySetupForm interactions', () => {
	let unmount: (() => Promise<void>) | undefined;

	afterEach(async () => {
		await unmount?.();
		unmount = undefined;
	});

	it('repairs the answer and mode in the prompt change handler and reports one valid config', async () => {
		const onChange = vi.fn();
		const rendered = await renderWithAct(
			<StudySetupForm
				catalogueType={SavedListType.KANJIS}
				value={{ ...defaultStudyConfig(), answer: 'kunyomi', mode: 'typed' }}
				onChange={onChange}
				onStart={vi.fn()}
				deckStatus={ready}
			/>,
		);
		unmount = rendered.unmount;
		const { container } = rendered;

		// Prompt becomes "meaning": the only valid answer is the character, which cannot be typed.
		await rendered.flush(() => choose(controlLabelled<HTMLSelectElement>(container, 'Card shows'), 'meaning'));

		expect(lastConfig(onChange)).toEqual(
			expect.objectContaining({ prompt: 'meaning', answer: 'character', mode: 'options' }),
		);
		expect(
			onChange.mock.calls.every(([config]) => config.prompt !== 'meaning' || config.answer === 'character'),
		).toBe(true);
	});

	it('writes a changed card count and keeps Start enabled', async () => {
		const onChange = vi.fn();
		const rendered = await renderWithAct(
			<StudySetupForm
				catalogueType={SavedListType.KANJIS}
				value={defaultStudyConfig()}
				onChange={onChange}
				onStart={vi.fn()}
				deckStatus={ready}
			/>,
		);
		unmount = rendered.unmount;
		const { container } = rendered;

		await rendered.flush(() => typeInto(controlLabelled<HTMLInputElement>(container, 'Cards per run'), '25'));

		expect(lastConfig(onChange)).toEqual(expect.objectContaining({ count: 25 }));
		expect(requireElement<HTMLButtonElement>(container, 'button[type="submit"]').disabled).toBe(false);
	});

	it('does not write an invalid count, and disables Start until it is fixed', async () => {
		const onChange = vi.fn();
		const rendered = await renderWithAct(
			<StudySetupForm
				catalogueType={SavedListType.KANJIS}
				value={defaultStudyConfig()}
				onChange={onChange}
				onStart={vi.fn()}
				deckStatus={ready}
			/>,
		);
		unmount = rendered.unmount;
		const { container } = rendered;

		await rendered.flush(() => typeInto(controlLabelled<HTMLInputElement>(container, 'Cards per run'), '0'));

		expect(onChange).not.toHaveBeenCalled();
		expect(requireElement<HTMLButtonElement>(container, 'button[type="submit"]').disabled).toBe(true);
	});

	it('follows a value that changed from outside without echoing it back', async () => {
		const onChange = vi.fn();
		const initial = defaultStudyConfig();
		const rendered = await renderWithAct(
			<StudySetupForm
				catalogueType={SavedListType.KANJIS}
				value={initial}
				onChange={onChange}
				onStart={vi.fn()}
				deckStatus={ready}
			/>,
		);
		unmount = rendered.unmount;

		await rendered.rerender(
			<StudySetupForm
				catalogueType={SavedListType.KANJIS}
				value={{ ...initial, answer: 'onyomi', count: 7, seed: 3 }}
				onChange={onChange}
				onStart={vi.fn()}
				deckStatus={ready}
			/>,
		);

		expect(controlLabelled<HTMLSelectElement>(rendered.container, 'You answer with').value).toBe('onyomi');
		expect(controlLabelled<HTMLInputElement>(rendered.container, 'Cards per run').value).toBe('7');
		expect(onChange).not.toHaveBeenCalled();
	});
});
