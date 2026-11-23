// @vitest-environment jsdom
import { afterEach, describe, expect, it, vi } from 'vitest';
import { click } from '@/test/formEvents';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import { AnswerOptions } from './index';

const options = ['one', 'study', 'water', 'fire'];

const press = (key: string, target: EventTarget = window) => {
	target.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true, cancelable: true }));
};

describe('AnswerOptions', () => {
	let unmount: (() => Promise<void>) | undefined;

	afterEach(async () => {
		await unmount?.();
		unmount = undefined;
	});

	it('renders four numbered buttons and selects on click', async () => {
		const onSelect = vi.fn();
		const rendered = await renderWithAct(<AnswerOptions options={options} japanese={false} onSelect={onSelect} />);
		unmount = rendered.unmount;

		const buttons = rendered.container.querySelectorAll('button');
		expect(buttons).toHaveLength(4);
		expect(buttons[1].getAttribute('aria-keyshortcuts')).toBe('2');

		click(buttons[2]);

		expect(onSelect).toHaveBeenCalledWith('water');
	});

	it('selects the matching option when a number key is pressed anywhere', async () => {
		const onSelect = vi.fn();
		const rendered = await renderWithAct(<AnswerOptions options={options} japanese={false} onSelect={onSelect} />);
		unmount = rendered.unmount;

		await rendered.flush(() => press('2'));

		expect(onSelect).toHaveBeenCalledTimes(1);
		expect(onSelect).toHaveBeenCalledWith('study');
	});

	it('ignores keys outside 1 to 4 and keys typed into an input', async () => {
		const onSelect = vi.fn();
		const rendered = await renderWithAct(
			<div>
				<input aria-label="somewhere else" />
				<AnswerOptions options={options} japanese={false} onSelect={onSelect} />
			</div>,
		);
		unmount = rendered.unmount;

		await rendered.flush(() => press('5'));
		await rendered.flush(() => press('a'));
		await rendered.flush(() => press('1', requireElement<HTMLInputElement>(rendered.container, 'input')));

		expect(onSelect).not.toHaveBeenCalled();
	});

	it('does nothing while disabled', async () => {
		const onSelect = vi.fn();
		const rendered = await renderWithAct(
			<AnswerOptions options={options} japanese={false} onSelect={onSelect} disabled />,
		);
		unmount = rendered.unmount;

		await rendered.flush(() => press('1'));

		expect(onSelect).not.toHaveBeenCalled();
		expect(rendered.container.querySelector('button')?.hasAttribute('disabled')).toBe(true);
	});
});
