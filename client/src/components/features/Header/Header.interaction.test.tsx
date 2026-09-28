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

const must = <T,>(element: T | null): T => {
	if (element === null) throw new Error('Expected element to be rendered');
	return element;
};

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

	it('lists every section in one order, with the four dictionaries in the Japanese Material group', () => {
		const primary = must(container.querySelector<HTMLUListElement>('#primary-navigation > ul'));
		const hrefs = Array.from(primary.querySelectorAll('a')).map((link) => link.getAttribute('href'));

		expect(hrefs).toEqual([
			'/articles',
			'/catalogues',
			'/radicals',
			'/kanjis',
			'/words',
			'/sentences',
			'/community',
			'/dashboard',
		]);

		// The group is a disclosure only in the middle width band; CSS lays it out flat in the
		// mobile menu and on wide screens, so the links live in the group's own list.
		const group = must(primary.querySelector<HTMLUListElement>(':scope > li > #material-nav-group'));
		expect(Array.from(group.querySelectorAll(':scope > li > a')).map((link) => link.textContent)).toEqual([
			'Radicals',
			'Kanji',
			'Words',
			'Sentences',
		]);
	});

	it('opens and closes Japanese Material as a disclosure, and closes it on outside click', () => {
		const button = must(container.querySelector<HTMLButtonElement>('button[aria-controls="material-nav-group"]'));
		const list = must(container.querySelector<HTMLUListElement>('#material-nav-group'));

		expect(button.getAttribute('aria-expanded')).toBe('false');
		expect(list.hidden).toBe(true);

		click(button);
		expect(button.getAttribute('aria-expanded')).toBe('true');
		expect(list.hidden).toBe(false);

		click(button);
		expect(button.getAttribute('aria-expanded')).toBe('false');

		click(button);
		act(() => {
			document.body.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
		});
		expect(button.getAttribute('aria-expanded')).toBe('false');
		expect(list.hidden).toBe(true);
	});

	it('keeps "+ New" in the account cluster, before the user name', () => {
		const account = must(container.querySelector<HTMLUListElement>('ul[aria-label="Account"]'));
		const button = must(account.querySelector<HTMLButtonElement>('button[aria-controls="new-nav-group"]'));
		const userLink = must(account.querySelector<HTMLAnchorElement>('a[href="/dashboard"]'));

		expect(button.textContent).toContain('New');
		expect(button.compareDocumentPosition(userLink) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
	});

	it('opens and closes "+ New" as a disclosure with all three targets', () => {
		const button = must(container.querySelector<HTMLButtonElement>('button[aria-controls="new-nav-group"]'));
		const list = must(container.querySelector<HTMLUListElement>('#new-nav-group'));

		expect(button.getAttribute('aria-expanded')).toBe('false');
		expect(list.hidden).toBe(true);

		click(button);
		expect(button.getAttribute('aria-expanded')).toBe('true');
		expect(list.hidden).toBe(false);
		expect(Array.from(list.querySelectorAll('a')).map((link) => link.getAttribute('href'))).toEqual([
			'/newarticle',
			'/catalogues/new',
			'/newpost',
		]);

		click(button);
		expect(button.getAttribute('aria-expanded')).toBe('false');
		expect(list.hidden).toBe(true);
	});

	it('closes an open group on Escape and returns focus to its button', () => {
		const button = must(container.querySelector<HTMLButtonElement>('button[aria-controls="new-nav-group"]'));
		click(button);
		const firstLink = must(container.querySelector<HTMLAnchorElement>('#new-nav-group a'));
		firstLink.focus();

		keydown(firstLink, 'Escape');

		expect(button.getAttribute('aria-expanded')).toBe('false');
		expect(document.activeElement).toBe(button);
	});

	it('closes an open group when clicking outside of it', () => {
		const button = must(container.querySelector<HTMLButtonElement>('button[aria-controls="new-nav-group"]'));
		click(button);
		expect(button.getAttribute('aria-expanded')).toBe('true');

		act(() => {
			document.body.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
		});

		expect(button.getAttribute('aria-expanded')).toBe('false');
	});

	it('toggles the collapsed mobile menu through an aria-expanded button', () => {
		const toggle = must(container.querySelector<HTMLButtonElement>('button[aria-controls="primary-navigation"]'));
		expect(toggle.getAttribute('aria-expanded')).toBe('false');

		click(toggle);
		expect(toggle.getAttribute('aria-expanded')).toBe('true');
		expect(toggle.getAttribute('aria-label')).toBe('Close navigation');

		click(toggle);
		expect(toggle.getAttribute('aria-expanded')).toBe('false');
	});

	it('closes the mobile menu on Escape (focus back to the toggle) and on outside click', () => {
		const toggle = must(container.querySelector<HTMLButtonElement>('button[aria-controls="primary-navigation"]'));
		click(toggle);
		const firstLink = must(container.querySelector<HTMLAnchorElement>('#primary-navigation a'));
		firstLink.focus();
		keydown(firstLink, 'Escape');
		expect(toggle.getAttribute('aria-expanded')).toBe('false');
		expect(document.activeElement).toBe(toggle);

		click(toggle);
		expect(toggle.getAttribute('aria-expanded')).toBe('true');
		act(() => {
			document.body.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
		});
		expect(toggle.getAttribute('aria-expanded')).toBe('false');
	});
});
