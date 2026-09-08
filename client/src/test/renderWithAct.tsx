import type { ReactElement } from 'react';
import { act } from 'react';
import { createRoot } from 'react-dom/client';

/**
 * Most tests in this repo render through `renderToStaticMarkup`, which never runs effects. Provider
 * behaviour that only exists inside `useEffect` — session restore, the unauthorized listener, the
 * cross-tab storage listener — is invisible to that approach, so these tests opt into jsdom with a
 * `@vitest-environment jsdom` docblock and mount for real.
 *
 * This wrapper exists instead of a testing library because the repo has none, and everything needed
 * here is a root, an `act` boundary, and a way to unmount.
 */
/**
 * `querySelector` with the "it rendered" assumption made explicit, so a markup change surfaces as a
 * named failure instead of a null dereference further down the test.
 */
export const requireElement = <T extends Element>(root: ParentNode, selector: string): T => {
	const element = root.querySelector<T>(selector);

	if (!element) {
		throw new Error(`Expected to find "${selector}" in the rendered output.`);
	}

	return element;
};

export const renderWithAct = async (ui: ReactElement) => {
	(globalThis as { IS_REACT_ACT_ENVIRONMENT?: boolean }).IS_REACT_ACT_ENVIRONMENT = true;

	const container = document.createElement('div');
	document.body.appendChild(container);
	const root = createRoot(container);

	await act(async () => {
		root.render(ui);
	});

	return {
		container,
		/** Runs `callback` and flushes every state update and microtask it schedules. */
		flush: async (callback: () => void | Promise<void>) => {
			await act(async () => {
				await callback();
			});
		},
		unmount: async () => {
			await act(async () => {
				root.unmount();
			});
			container.remove();
		},
	};
};
