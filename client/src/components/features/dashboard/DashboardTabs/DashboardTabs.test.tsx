import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import type { DashboardTab } from '@/routes/Dashboard/dashboardSearchParams';
import { DashboardTabs } from './';

const render = (active: DashboardTab, isAdmin = false) =>
	renderToStaticMarkup(
		<MemoryRouter initialEntries={['/dashboard?tab=lists&q=old']}>
			<DashboardTabs active={active} isAdmin={isAdmin} />
		</MemoryRouter>,
	);

const links = (html: string) => Array.from(html.matchAll(/<a [^>]*>([^<]+)<\/a>/g), (match) => match[0]);

describe('DashboardTabs', () => {
	it('is a named navigation landmark of links', () => {
		const html = render('articles');

		expect(html).toContain('<nav aria-label="Dashboard sections"');
		expect(html).not.toContain('role="tab');
	});

	it('links each section to its own clean URL', () => {
		const html = render('lists');

		expect(html).toContain('href="/dashboard">Articles</a>');
		expect(html).toContain('href="/dashboard?tab=lists">Lists</a>');
	});

	it('marks only the current section with aria-current', () => {
		const html = render('lists');
		const current = links(html).filter((link) => link.includes('aria-current="page"'));

		expect(current).toHaveLength(1);
		expect(current[0]).toContain('>Lists<');
	});

	it('shows the review queue to admins only', () => {
		expect(render('articles', false)).not.toContain('Review queue');
		expect(render('articles', true)).toContain('href="/dashboard?tab=review">Review queue</a>');
	});
});
