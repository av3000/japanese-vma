// @vitest-environment jsdom
import { afterEach, describe, expect, it, vi } from 'vitest';
import { submitForm, typeInto } from '@/test/formEvents';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import { AnswerInput } from './index';

const composition = (input: HTMLInputElement, type: 'compositionstart' | 'compositionend') => {
	input.dispatchEvent(new Event(type, { bubbles: true }));
};

/** Returns false when the handler called `preventDefault()`, as the browser would then skip the submit. */
const pressEnter = (input: HTMLInputElement) =>
	input.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', bubbles: true, cancelable: true }));

describe('AnswerInput', () => {
	let unmount: (() => Promise<void>) | undefined;

	afterEach(async () => {
		await unmount?.();
		unmount = undefined;
	});

	it('submits the trimmed answer', async () => {
		const onSubmit = vi.fn();
		const rendered = await renderWithAct(
			<AnswerInput label="Kun’yomi (hiragana)" japanese onSubmit={onSubmit} cardKey={1} />,
		);
		unmount = rendered.unmount;

		const input = requireElement<HTMLInputElement>(rendered.container, 'input');
		expect(input.getAttribute('lang')).toBe('ja');

		typeInto(input, ' つぐ ');
		submitForm(requireElement<HTMLFormElement>(rendered.container, 'form'));

		expect(onSubmit).toHaveBeenCalledWith('つぐ');
	});

	it('never submits an empty answer', async () => {
		const onSubmit = vi.fn();
		const rendered = await renderWithAct(
			<AnswerInput label="Meaning" japanese={false} onSubmit={onSubmit} cardKey={1} />,
		);
		unmount = rendered.unmount;

		const button = requireElement<HTMLButtonElement>(rendered.container, 'button[type="submit"]');
		expect(button.disabled).toBe(true);

		typeInto(requireElement<HTMLInputElement>(rendered.container, 'input'), '   ');
		submitForm(requireElement<HTMLFormElement>(rendered.container, 'form'));

		expect(onSubmit).not.toHaveBeenCalled();
	});

	it('swallows Enter while an IME composition is in progress, then submits after it ends', async () => {
		const onSubmit = vi.fn();
		const rendered = await renderWithAct(<AnswerInput label="Reading" japanese onSubmit={onSubmit} cardKey={1} />);
		unmount = rendered.unmount;

		const input = requireElement<HTMLInputElement>(rendered.container, 'input');
		const form = requireElement<HTMLFormElement>(rendered.container, 'form');

		typeInto(input, 'つ');
		await rendered.flush(() => composition(input, 'compositionstart'));
		typeInto(input, 'つぐ');

		let defaultAllowed = true;
		await rendered.flush(() => {
			defaultAllowed = pressEnter(input);
		});
		expect(defaultAllowed).toBe(false);

		// Even a submit that slips through while composing is ignored.
		submitForm(form);
		expect(onSubmit).not.toHaveBeenCalled();

		await rendered.flush(() => composition(input, 'compositionend'));
		await rendered.flush(() => {
			defaultAllowed = pressEnter(input);
		});
		expect(defaultAllowed).toBe(true);

		submitForm(form);
		expect(onSubmit).toHaveBeenCalledWith('つぐ');
	});

	it('clears the field when the card changes', async () => {
		const rendered = await renderWithAct(
			<AnswerInput label="Meaning" japanese={false} onSubmit={vi.fn()} cardKey={1} />,
		);
		unmount = rendered.unmount;

		const input = requireElement<HTMLInputElement>(rendered.container, 'input');
		typeInto(input, 'study');
		expect(input.value).toBe('study');

		await rendered.rerender(<AnswerInput label="Meaning" japanese={false} onSubmit={vi.fn()} cardKey={2} />);

		expect(input.value).toBe('');
	});
});
