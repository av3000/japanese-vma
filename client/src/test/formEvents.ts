import { act } from 'react';

/**
 * Drivers for controlled form controls in jsdom tests. React tracks a control's value through the
 * element's native setter, so assigning `.value` directly would not reach `onChange`.
 */

const setNativeValue = (element: HTMLInputElement | HTMLSelectElement, value: string) => {
	const prototype = element instanceof HTMLSelectElement ? HTMLSelectElement.prototype : HTMLInputElement.prototype;

	Object.getOwnPropertyDescriptor(prototype, 'value')?.set?.call(element, value);
};

export const typeInto = (input: HTMLInputElement, value: string) => {
	act(() => {
		setNativeValue(input, value);
		input.dispatchEvent(new Event('input', { bubbles: true }));
	});
};

export const choose = (select: HTMLSelectElement, value: string) => {
	act(() => {
		setNativeValue(select, value);
		select.dispatchEvent(new Event('change', { bubbles: true }));
	});
};

/** What Enter in a text input does: submit the form. */
export const submitForm = (form: HTMLFormElement) => {
	act(() => {
		form.requestSubmit();
	});
};

export const click = (element: Element) => {
	act(() => {
		element.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
	});
};

/** The control a visually hidden label names, found the way assistive technology finds it. */
export const controlLabelled = <T extends HTMLElement>(root: ParentNode, label: string): T => {
	const match = Array.from(root.querySelectorAll('label')).find((element) => element.textContent === label);
	const control = match?.htmlFor ? root.querySelector<T>(`[id="${match.htmlFor}"]`) : null;

	if (!control) {
		throw new Error(`Expected a control labelled "${label}".`);
	}

	return control;
};
