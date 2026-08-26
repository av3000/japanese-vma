import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import Dashboard from './index';
import { useAuth } from '@/hooks/useAuth';

vi.mock('@/hooks/useAuth', () => ({
	useAuth: vi.fn(),
}));

vi.mock('./DashboardArticlesPanel', () => ({ default: () => <div>Articles panel</div> }));
vi.mock('./DashboardCataloguesPanel', () => ({ default: () => <div>Catalogues panel</div> }));

describe('Dashboard', () => {
	it('renders the dashboard loading family while authentication is restoring', () => {
		vi.mocked(useAuth).mockReturnValue({
			isAuthenticated: false,
			isLoading: true,
			user: null,
		} as never);

		const html = renderToStaticMarkup(<Dashboard />);

		expect(html).toContain('aria-busy="true"');
		expect(html).toContain('data-loading-family="dashboard"');
		expect(html).toContain('Loading page.');
		expect(html).not.toContain('alt="Loading..."');
	});
});
