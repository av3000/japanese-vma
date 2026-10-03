// @vitest-environment jsdom
import { afterEach, describe, expect, it, vi } from 'vitest';
import { renderWithAct } from '@/test/renderWithAct';
import { kanjiCards } from '../fixtures';
import { AnswerFeedback } from './index';

const pressEnter = (repeat = false): void => {
	window.dispatchEvent(new KeyboardEvent('keydown', { key: 'Enter', repeat, bubbles: true, cancelable: true }));
};

describe('AnswerFeedback keyboard', () => {
	let unmount: (() => Promise<void>) | undefined;

	afterEach(async () => {
		await unmount?.();
		unmount = undefined;
	});

	it('advances on Enter, but not on a held key repeating from the typed submit', async () => {
		const onNext = vi.fn();
		const rendered = await renderWithAct(
			<AnswerFeedback
				card={kanjiCards[0]}
				correct
				matched="study"
				given="study"
				japanese={false}
				isLast={false}
				onNext={onNext}
			/>,
		);
		unmount = rendered.unmount;

		await rendered.flush(() => pressEnter(true));
		expect(onNext).not.toHaveBeenCalled();

		await rendered.flush(() => pressEnter());
		expect(onNext).toHaveBeenCalledTimes(1);
	});

	it('focuses the Next button on mount and calls the latest onNext without re-subscribing', async () => {
		const first = vi.fn();
		const second = vi.fn();
		const addEventListener = vi.spyOn(window, 'addEventListener');
		const props = {
			card: kanjiCards[0],
			correct: true,
			matched: 'study',
			given: 'study',
			japanese: false,
			isLast: false,
		};

		const rendered = await renderWithAct(<AnswerFeedback {...props} onNext={first} />);
		unmount = rendered.unmount;

		expect(document.activeElement?.textContent).toBe('Next card');
		const keydownSubscriptions = () => addEventListener.mock.calls.filter(([type]) => type === 'keydown').length;
		const subscribedAfterMount = keydownSubscriptions();

		await rendered.rerender(<AnswerFeedback {...props} onNext={second} />);
		await rendered.flush(() => pressEnter());

		expect(first).not.toHaveBeenCalled();
		expect(second).toHaveBeenCalledTimes(1);
		expect(keydownSubscriptions()).toBe(subscribedAfterMount);

		addEventListener.mockRestore();
	});
});
