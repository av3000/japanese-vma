// @vitest-environment jsdom
import { act } from 'react';
import { createRoot, type Root } from 'react-dom/client';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { FilterBar } from './';

(globalThis as { IS_REACT_ACT_ENVIRONMENT?: boolean }).IS_REACT_ACT_ENVIRONMENT = true;

const SORTS = [
	{ value: 'new', label: 'Newest' },
	{ value: 'pop', label: 'Popular' },
] as const;

const must = <T,>(element: T | null): T => {
	if (element === null) throw new Error('Expected element to be rendered');
	return element;
};

let container: HTMLDivElement;
let root: Root;

const render = (element: React.ReactElement) => {
	act(() => root.render(element));
};

const bar = ({
	onSubmit = vi.fn(),
	resetActive = false,
	submitDisabled = false,
	hint,
}: {
	onSubmit?: () => void;
	resetActive?: boolean;
	submitDisabled?: boolean;
	hint?: string;
} = {}) => (
	<FilterBar onSubmit={onSubmit} label="Article filters">
		<FilterBar.Search
			label="Search articles"
			value="grammar"
			onChange={vi.fn()}
			submitDisabled={submitDisabled}
			hint={hint}
		/>
		<FilterBar.Filters>
			<FilterBar.Select label="Type" value="all" options={[{ value: 'all', label: 'All' }]} onChange={vi.fn()} />
		</FilterBar.Filters>
		<FilterBar.Sort value="new" options={SORTS} onChange={vi.fn()} />
		<FilterBar.Reset active={resetActive} onClick={vi.fn()} />
	</FilterBar>
);

const clickSubmit = () => {
	const button = must(container.querySelector<HTMLButtonElement>('button[type="submit"]'));

	act(() => {
		button.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
	});
};

beforeEach(() => {
	container = document.createElement('div');
	document.body.appendChild(container);
	root = createRoot(container);
});

afterEach(() => {
	act(() => root.unmount());
	container.remove();
});

describe('FilterBar', () => {
	it('renders a native search form, named for assistive technology', () => {
		render(bar());

		const form = must(container.querySelector('form'));
		expect(form.getAttribute('role')).toBe('search');
		expect(form.getAttribute('aria-label')).toBe('Article filters');
	});

	it('labels every control, even though the labels are visually hidden', () => {
		render(bar());

		const controls = Array.from(container.querySelectorAll<HTMLInputElement | HTMLSelectElement>('input, select'));

		expect(controls).toHaveLength(3);
		expect(controls.map((control) => control.labels?.[0]?.textContent)).toEqual([
			'Search articles',
			'Type',
			'Sort by',
		]);
	});

	it('submits from the search button without reloading the page', () => {
		const onSubmit = vi.fn();
		render(bar({ onSubmit }));

		expect(must(container.querySelector<HTMLButtonElement>('button[type="submit"]')).textContent).toBe('Search');

		const submitEvents: Event[] = [];
		must(container.querySelector('form')).addEventListener('submit', (submit) => submitEvents.push(submit));
		clickSubmit();

		expect(onSubmit).toHaveBeenCalledTimes(1);
		expect(submitEvents).toHaveLength(1);
		expect(submitEvents[0].defaultPrevented).toBe(true);
	});

	it('submits when the form itself is submitted, which is what Enter in the input does', () => {
		const onSubmit = vi.fn();
		render(bar({ onSubmit }));

		act(() => {
			must(container.querySelector('form')).requestSubmit();
		});

		expect(onSubmit).toHaveBeenCalledTimes(1);
	});

	it('does not submit while the search button is disabled', () => {
		const onSubmit = vi.fn();
		render(bar({ onSubmit, submitDisabled: true }));

		clickSubmit();

		expect(onSubmit).not.toHaveBeenCalled();
	});

	it('renders Reset only when the caller says filters are active', () => {
		render(bar({ resetActive: false }));
		expect(container.textContent).not.toContain('Reset');

		render(bar({ resetActive: true }));
		expect(container.textContent).toContain('Reset');
	});

	it('links the hint to the search input', () => {
		render(bar({ hint: 'Enter at least 2 characters.' }));

		const input = must(container.querySelector('input'));
		const hint = must(container.querySelector(`[id="${input.getAttribute('aria-describedby')}"]`));

		expect(hint.textContent).toBe('Enter at least 2 characters.');
	});

	it('has no describedby, and renders no hint, without one', () => {
		render(bar());

		expect(must(container.querySelector('input')).hasAttribute('aria-describedby')).toBe(false);
		expect(container.querySelector('p')).toBeNull();
	});

	it('uses plain form controls, not ARIA menu roles', () => {
		render(bar({ resetActive: true }));

		expect(
			container.querySelector('[role="menu"], [role="menubar"], [role="menuitem"], [role="listbox"]'),
		).toBeNull();
	});
});
