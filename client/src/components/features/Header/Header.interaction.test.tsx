// @vitest-environment jsdom
import { act } from 'react';
import { createRoot, type Root } from 'react-dom/client';
import { MemoryRouter } from 'react-router-dom';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import Header from './index';

(globalThis as { IS_REACT_ACT_ENVIRONMENT?: boolean }).IS_REACT_ACT_ENVIRONMENT = true;

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({ isAuthenticated: true, isLoading: false, user: { id: 1, name: 'Alana' }, logout: vi.fn() }),
}));

vi.mock('@/components/features/SocketStatusIndicator', () => ({
	default: () => <span>Socket status</span>,
}));

let container: HTMLDivElement;
let root: Root;

const render = () => {
	act(() => {
		root.render(
			<MemoryRouter>
				<Header />
			</MemoryRouter>,
		);
	});
};

const click = (element: Element) => {
	act(() => {
		element.dispatchEvent(new MouseEvent('click', { bubbles: true }));
	});
};

const keydown = (element: Element, key: string) => {
	act(() => {
		element.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true }));
	});
};

beforeEach(() => {
	container = document.createElement('div');
	document.body.appendChild(container);
	root = createRoot(container);
	render();
});

afterEach(() => {
	act(() => root.unmount());
	container.remove();
});

describe('Header navigation semantics', () => {
	it('exposes a main navigation landmark with plain link lists, not ARIA menus', () => {
		const nav = container.querySelector('nav[aria-label="Main"]');
		expect(nav).not.toBeNull();
		expect(container.querySelector('[role="menu"], [role="menubar"], [role="menuitem"]')).toBeNull();
		expect(nav?.querySelectorAll('ul').length).toBeGreaterThanOrEqual(2);
	});

	it('opens and closes a navigation group as a disclosure', () => {
		const button = container.querySelector<HTMLButtonElement>('button[aria-controls="material-nav-group"]')!;
		const list = container.querySelector<HTMLUListElement>('#material-nav-group')!;

		expect(button.getAttribute('aria-expanded')).toBe('false');
		expect(list.hidden).toBe(true);

		click(button);
		expect(button.getAttribute('aria-expanded')).toBe('true');
		expect(list.hidden).toBe(false);
		expect(list.querySelector('a[href="/kanjis"]')).not.toBeNull();

		click(button);
		expect(button.getAttribute('aria-expanded')).toBe('false');
		expect(list.hidden).toBe(true);
	});

	it('closes an open group on Escape and returns focus to its button', () => {
		const button = container.querySelector<HTMLButtonElement>('button[aria-controls="new-nav-group"]')!;
		click(button);
		const firstLink = container.querySelector<HTMLAnchorElement>('#new-nav-group a')!;
		firstLink.focus();

		keydown(firstLink, 'Escape');

		expect(button.getAttribute('aria-expanded')).toBe('false');
		expect(document.activeElement).toBe(button);
	});

	it('closes an open group when clicking outside of it', () => {
		const button = container.querySelector<HTMLButtonElement>('button[aria-controls="material-nav-group"]')!;
		click(button);
		expect(button.getAttribute('aria-expanded')).toBe('true');

		act(() => {
			document.body.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
		});

		expect(button.getAttribute('aria-expanded')).toBe('false');
	});

	it('toggles the collapsed mobile menu through an aria-expanded button', () => {
		const toggle = container.querySelector<HTMLButtonElement>('button[aria-controls="primary-navigation"]')!;
		expect(toggle.getAttribute('aria-expanded')).toBe('false');

		click(toggle);
		expect(toggle.getAttribute('aria-expanded')).toBe('true');
		expect(toggle.getAttribute('aria-label')).toBe('Close navigation');

		click(toggle);
		expect(toggle.getAttribute('aria-expanded')).toBe('false');
	});
});
