// @vitest-environment jsdom
import { act } from 'react';
import { MemoryRouter } from 'react-router-dom';
import { afterAll, afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import { click, typeInto } from '@/test/formEvents';
import { renderWithAct, requireElement } from '@/test/renderWithAct';
import { HeaderSearchDrawer } from './HeaderSearchDrawer';

const navigate = vi.fn();

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return { ...actual, useNavigate: () => navigate };
});

// jsdom has no showModal(); the real modal behaviour (focus trap, inert page) is covered by the
// Storybook play functions in a browser.
const original = { showModal: HTMLDialogElement.prototype.showModal, close: HTMLDialogElement.prototype.close };

beforeAll(() => {
	HTMLDialogElement.prototype.showModal = function showModal(this: HTMLDialogElement) {
		this.setAttribute('open', '');
	};
	HTMLDialogElement.prototype.close = function close(this: HTMLDialogElement) {
		this.removeAttribute('open');
	};
});

afterAll(() => {
	HTMLDialogElement.prototype.showModal = original.showModal;
	HTMLDialogElement.prototype.close = original.close;
});

let view: Awaited<ReturnType<typeof renderWithAct>>;

/** useModal opens and restores focus on short timers. */
const settle = () => act(() => new Promise((resolve) => setTimeout(resolve, 30)));

const trigger = () => requireElement<HTMLButtonElement>(view.container, 'button[aria-haspopup="dialog"]');
const dialog = () => requireElement<HTMLDialogElement>(view.container, 'dialog');
const input = () => requireElement<HTMLInputElement>(dialog(), 'input[type="search"]');
const buttonNamed = (name: string) => {
	const match = Array.from(dialog().querySelectorAll('button')).find((button) => button.textContent?.trim() === name);
	if (!match) throw new Error(`No "${name}" button in the drawer`);
	return match;
};

const open = async () => {
	act(() => trigger().focus());
	click(trigger());
	await settle();
};

beforeEach(async () => {
	navigate.mockClear();
	localStorage.clear();
	view = await renderWithAct(
		<MemoryRouter>
			<HeaderSearchDrawer />
		</MemoryRouter>,
	);
});

afterEach(async () => {
	await view.unmount();
});

describe('HeaderSearchDrawer', () => {
	it('is an icon button that opens a labelled search dialog with the input focused', async () => {
		expect(trigger().getAttribute('aria-label')).toBe('Search');
		expect(trigger().getAttribute('aria-controls')).toBe(dialog().id);
		expect(dialog().open).toBe(false);

		await open();

		expect(dialog().open).toBe(true);
		expect(dialog().getAttribute('aria-label')).toBe('Search');
		expect(trigger().getAttribute('aria-expanded')).toBe('true');
		expect(dialog().querySelector('form[role="search"]')).not.toBeNull();
		expect(document.activeElement).toBe(input());
	});

	it('words the primary button after the scope: Browse without a query, Search with one', async () => {
		await open();
		expect(requireElement(dialog(), 'button[type="submit"]').textContent).toBe('Browse articles');

		typeInto(input(), '水');
		expect(requireElement(dialog(), 'button[type="submit"]').textContent).toBe('Search kanji');

		typeInto(input(), 'たべる');
		expect(requireElement(dialog(), 'button[type="submit"]').textContent).toBe('Search words');
	});

	it('uses plain buttons for the rows, not a listbox', async () => {
		await open();
		typeInto(input(), '水');

		expect(dialog().querySelector('[role="listbox"], [role="option"]')).toBeNull();
		click(buttonNamed('Sentences with 水↵'));

		expect(navigate).toHaveBeenCalledWith('/sentences?keyword=%E6%B0%B4');
		expect(dialog().open).toBe(false);
	});

	it('searches the current scope with its refine from the primary button', async () => {
		await open();
		typeInto(input(), '水');
		click(buttonNamed('N3'));
		act(() => requireElement<HTMLFormElement>(dialog(), 'form').requestSubmit());

		expect(navigate).toHaveBeenCalledWith('/kanjis?keyword=%E6%B0%B4&jlpt=3');
	});

	it.each([
		['Cancel', () => click(buttonNamed('Cancel'))],
		[
			'Escape in the input',
			() =>
				act(() => {
					input().dispatchEvent(
						new KeyboardEvent('keydown', { key: 'Escape', bubbles: true, cancelable: true }),
					);
				}),
		],
	])('closes on %s and returns focus to the trigger', async (_, close) => {
		await open();
		close();
		await settle();

		expect(dialog().open).toBe(false);
		expect(trigger().getAttribute('aria-expanded')).toBe('false');
		expect(document.activeElement).toBe(trigger());
		expect(navigate).not.toHaveBeenCalled();
	});
});
