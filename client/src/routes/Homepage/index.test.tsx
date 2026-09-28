import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it, vi } from 'vitest';
import Homepage from './index';

const authState = vi.hoisted(() => ({ value: { isAuthenticated: false, isLoading: false, user: null } as unknown }));

vi.mock('@/hooks/useAuth', () => ({ useAuth: () => authState.value }));

vi.mock('@/components/features/Homepage/ScopedSearch', () => ({ ScopedSearch: () => <div>[scoped-search]</div> }));
vi.mock('@/components/features/Homepage/CorpusStatsTiles', () => ({
	CorpusStatsTiles: () => <div>[corpus-tiles]</div>,
}));
vi.mock('@/components/features/Homepage/PhotoBand', () => ({ PhotoBand: () => <div>[photo-band]</div> }));
vi.mock('@/components/features/Homepage/LatestArticles', () => ({
	LatestArticles: () => <div>[latest-articles]</div>,
}));
vi.mock('@/components/features/Homepage/PopularCatalogues', () => ({
	PopularCatalogues: () => <div>[popular-catalogues]</div>,
}));

const SECTIONS = ['[scoped-search]', '[corpus-tiles]', '[photo-band]', '[latest-articles]', '[popular-catalogues]'];

const render = () =>
	renderToStaticMarkup(
		<MemoryRouter>
			<Homepage />
		</MemoryRouter>,
	);

describe('Homepage', () => {
	it.each([
		['a guest', { isAuthenticated: false, isLoading: false, user: null }],
		['a signed-in user', { isAuthenticated: true, isLoading: false, user: { id: 1, name: 'Hanako' } }],
	])('renders the same page for %s: heading, then every section in order', (_, auth) => {
		authState.value = auth;
		const html = render();

		expect(html).toContain('<h1');
		expect(html).toContain('Find your next reading');
		const positions = SECTIONS.map((section) => html.indexOf(section));
		expect(positions.every((position) => position > html.indexOf('Find your next reading'))).toBe(true);
		expect(positions).toEqual([...positions].sort((a, b) => a - b));
	});

	it('renders identical markup for guests and signed-in users', () => {
		authState.value = { isAuthenticated: false, isLoading: false, user: null };
		const guest = render();
		authState.value = { isAuthenticated: true, isLoading: false, user: { id: 1, name: 'Hanako' } };

		expect(render()).toBe(guest);
	});

	it('has no sign-up call to action and no feed greeting in the page body', () => {
		const html = render();

		expect(html).not.toMatch(/sign\s?up/i);
		expect(html).not.toContain('/register');
		expect(html).not.toContain('Welcome to your feed');
	});
});
