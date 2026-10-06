// @vitest-environment jsdom
import { act } from 'react';
import { createRoot, type Root } from 'react-dom/client';
import { MemoryRouter } from 'react-router-dom';
import { afterAll, afterEach, beforeAll, beforeEach, describe, expect, it, vi } from 'vitest';
import Header from './index';

(globalThis as { IS_REACT_ACT_ENVIRONMENT?: boolean }).IS_REACT_ACT_ENVIRONMENT = true;

const logout = vi.hoisted(() => vi.fn());

vi.mock('@/hooks/useAuth', () => ({
	useAuth: () => ({ isAuthenticated: true, isLoading: false, user: { id: 1, name: 'Alana' }, logout }),
}));

vi.mock('@/components/features/SocketStatusIndicator', () => ({
	default: () => <span>Socket status</span>,
}));

// jsdom has no showModal(); the real modal behaviour is covered by the Storybook play functions.
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

const must = <T,>(element: T | null): T => {
	if (element === null) throw new Error('Expected element to be rendered');
	return element;
};

let container: HTMLDivElement;
let root: Root;

const click = (element: Element) => {
	act(() => {
		element.dispatchEvent(new MouseEvent('click', { bubbles: true, cancelable: true }));
	});
};

const keydown = (element: Element, key: string) => {
	act(() => {
		element.dispatchEvent(new KeyboardEvent('keydown', { key, bubbles: true }));
	});
};

const mouseupOutside = () => {
	act(() => {
		document.body.dispatchEvent(new MouseEvent('mouseup', { bubbles: true }));
	});
};

/** useModal opens and restores focus on short timers. */
const settle = () => act(() => new Promise((resolve) => setTimeout(resolve, 30)));

const hrefs = (scope: ParentNode) => Array.from(scope.querySelectorAll('a')).map((link) => link.getAttribute('href'));

const desktopNav = () => must(container.querySelector<HTMLElement>('header > div > nav[aria-label="Main"]'));
const accountList = () => must(container.querySelector<HTMLUListElement>('header > div > ul[aria-label="Account"]'));
const menuButton = () => must(container.querySelector<HTMLButtonElement>('button[aria-label="Open navigation"]'));
const menuDialog = () => must(container.querySelector<HTMLDialogElement>('dialog[aria-label="Navigation"]'));

beforeEach(() => {
	logout.mockClear();
	container = document.createElement('div');
	document.body.appendChild(container);
	root = createRoot(container);
	act(() => {
		root.render(
			<MemoryRouter>
				<Header />
			</MemoryRouter>,
		);
	});
});

afterEach(() => {
	act(() => root.unmount());
	container.remove();
});

describe('Header navigation semantics', () => {
	it('uses disclosures and plain link lists, never ARIA menus', () => {
		expect(container.querySelector('[role="menu"], [role="menubar"], [role="menuitem"]')).toBeNull();
		expect(
			Array.from(desktopNav().querySelectorAll('button[aria-controls]')).map((button) =>
				button.textContent?.trim(),
			),
		).toEqual(['Explore', 'Dictionary']);
	});

	it('groups Articles, Catalogues and Community under Explore, and the four dictionaries under Dictionary', () => {
		expect(hrefs(must(container.querySelector('#explore-nav-group')))).toEqual([
			'/articles',
			'/catalogues',
			'/community',
		]);
		expect(hrefs(must(container.querySelector('#dictionary-nav-group')))).toEqual([
			'/radicals',
			'/kanjis',
			'/words',
			'/sentences',
		]);
		expect(hrefs(desktopNav())).not.toContain('/dashboard');
	});

	it('opens and closes a group as a disclosure, and closes it on outside click', () => {
		const button = must(container.querySelector<HTMLButtonElement>('button[aria-controls="explore-nav-group"]'));
		const list = must(container.querySelector<HTMLUListElement>('#explore-nav-group'));

		expect(button.getAttribute('aria-expanded')).toBe('false');
		expect(list.hidden).toBe(true);

		click(button);
		expect(button.getAttribute('aria-expanded')).toBe('true');
		expect(list.hidden).toBe(false);

		click(button);
		expect(button.getAttribute('aria-expanded')).toBe('false');

		click(button);
		mouseupOutside();
		expect(list.hidden).toBe(true);
	});

	it('closes an open group on Escape and returns focus to its button', () => {
		const button = must(container.querySelector<HTMLButtonElement>('button[aria-controls="dictionary-nav-group"]'));
		click(button);
		const firstLink = must(container.querySelector<HTMLAnchorElement>('#dictionary-nav-group a'));
		firstLink.focus();

		keydown(firstLink, 'Escape');

		expect(button.getAttribute('aria-expanded')).toBe('false');
		expect(document.activeElement).toBe(button);
	});

	it('shows only the name in the bar, with Dashboard, a divider and Log out last behind it', () => {
		const button = must(
			accountList().querySelector<HTMLButtonElement>('button[aria-controls="account-nav-group"]'),
		);
		const list = must(container.querySelector<HTMLUListElement>('#account-nav-group'));

		expect(button.textContent?.trim()).toBe('Alana');
		expect(list.hidden).toBe(true);
		expect(accountList().textContent).not.toContain('Logged in as');

		click(button);
		expect(list.hidden).toBe(false);
		const items = Array.from(list.children);
		expect(items.map((item) => item.getAttribute('role') ?? item.textContent)).toEqual([
			'Dashboard',
			'separator',
			'Log out',
		]);
		expect(hrefs(list)).toEqual(['/dashboard']);

		click(must(items[2].querySelector('button')));
		expect(logout).toHaveBeenCalledTimes(1);
	});

	it('keeps the socket indicator just before the name', () => {
		const [socket, account] = Array.from(accountList().children);

		expect(socket.textContent).toBe('Socket status');
		expect(account.querySelector('button[aria-controls="account-nav-group"]')).not.toBeNull();
	});
});

describe('Header menu drawer', () => {
	it('opens a navigation dialog with the Explore and Dictionary sections and the account last', async () => {
		expect(menuButton().getAttribute('aria-haspopup')).toBe('dialog');
		expect(menuDialog().open).toBe(false);

		click(menuButton());
		await settle();

		expect(menuDialog().open).toBe(true);
		expect(menuButton().getAttribute('aria-expanded')).toBe('true');
		const nav = must(menuDialog().querySelector('nav[aria-label="Main"]'));
		expect(Array.from(nav.querySelectorAll('h2')).map((heading) => heading.textContent)).toEqual([
			'Explore',
			'Dictionary',
		]);
		expect(hrefs(nav)).toEqual([
			'/articles',
			'/catalogues',
			'/community',
			'/radicals',
			'/kanjis',
			'/words',
			'/sentences',
		]);

		const account = must(menuDialog().querySelector('ul[aria-label="Account"]'));
		expect(nav.compareDocumentPosition(account) & Node.DOCUMENT_POSITION_FOLLOWING).toBeTruthy();
		const accountButton = must(account.querySelector<HTMLButtonElement>('button[aria-controls]'));
		expect(accountButton.textContent?.trim()).toBe('Alana');
		click(accountButton);
		expect(hrefs(account)).toEqual(['/dashboard']);
		expect(account.textContent).toContain('Log out');
	});

	it('closes from its close button and returns focus to the menu button', async () => {
		act(() => menuButton().focus());
		click(menuButton());
		await settle();

		click(must(menuDialog().querySelector('button[aria-label="Close navigation"]')));
		await settle();

		expect(menuDialog().open).toBe(false);
		expect(document.activeElement).toBe(menuButton());
	});

	it('closes when a link is followed', async () => {
		click(menuButton());
		await settle();

		click(must(menuDialog().querySelector('a[href="/words"]')));
		await settle();

		expect(menuDialog().open).toBe(false);
	});
});
