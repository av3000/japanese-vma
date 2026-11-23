import { act } from 'react';
import { MemoryRouter, Route, Routes, useLocation, useNavigate, type Location, type To } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { renderWithAct } from '@/test/renderWithAct';
import Dashboard from './index';

/**
 * Test-only harness: mounts the real Dashboard route at a URL, with a probe that exposes the
 * current location and lets a test go back or forward the way the browser would. The data hooks
 * are mocked by each test file; this only supplies the router and a QueryClient.
 */
export const renderDashboardRoute = async (url: string) => {
	const probe: { location?: Location; navigate?: ReturnType<typeof useNavigate> } = {};

	const Probe = () => {
		probe.location = useLocation();
		probe.navigate = useNavigate();
		return null;
	};

	const view = await renderWithAct(
		<QueryClientProvider client={new QueryClient({ defaultOptions: { queries: { retry: false } } })}>
			<MemoryRouter initialEntries={[url]}>
				<Routes>
					<Route path="/dashboard" element={<Dashboard />} />
				</Routes>
				<Probe />
			</MemoryRouter>
		</QueryClientProvider>,
	);

	return {
		view,
		/** The current `?…` part of the URL, without the leading `?`. */
		search: () => (probe.location?.search ?? '').replace(/^\?/, ''),
		/** A path, or a history step: `-1` is the browser's back button. */
		navigate: (to: To | number) =>
			act(() => {
				probe.navigate?.(to as To);
			}),
	};
};
