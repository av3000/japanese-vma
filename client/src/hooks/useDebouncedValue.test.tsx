// @vitest-environment jsdom
import { act } from 'react';
import { createRoot, type Root } from 'react-dom/client';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useDebouncedValue } from './useDebouncedValue';

(globalThis as { IS_REACT_ACT_ENVIRONMENT?: boolean }).IS_REACT_ACT_ENVIRONMENT = true;

let container: HTMLDivElement;
let root: Root;
let latest: { debounced: string; flush: () => void };

const Probe = ({ value }: { value: string }) => {
	const [debounced, flush] = useDebouncedValue(value, 300);
	latest = { debounced, flush };

	return null;
};

const render = (value: string) => act(() => root.render(<Probe value={value} />));

beforeEach(() => {
	vi.useFakeTimers();
	container = document.createElement('div');
	root = createRoot(container);
});

afterEach(() => {
	act(() => root.unmount());
	vi.useRealTimers();
});

describe('useDebouncedValue', () => {
	it('starts with the current value', () => {
		render('water');

		expect(latest.debounced).toBe('water');
	});

	it('follows a change only after the delay', () => {
		render('water');
		render('fire');

		act(() => {
			vi.advanceTimersByTime(299);
		});
		expect(latest.debounced).toBe('water');

		act(() => {
			vi.advanceTimersByTime(1);
		});
		expect(latest.debounced).toBe('fire');
	});

	it('restarts the delay on every change, so only the last value lands', () => {
		render('w');
		render('wa');
		act(() => {
			vi.advanceTimersByTime(200);
		});
		render('wat');
		act(() => {
			vi.advanceTimersByTime(200);
		});

		expect(latest.debounced).toBe('w');

		act(() => {
			vi.advanceTimersByTime(100);
		});
		expect(latest.debounced).toBe('wat');
	});

	it('catches up immediately on flush', () => {
		render('water');
		render('fire');

		act(() => latest.flush());

		expect(latest.debounced).toBe('fire');
	});
});
