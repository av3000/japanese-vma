import type * as React from 'react';
import { MemoryRouter, Route, Routes, useLocation } from 'react-router-dom';
import type { Decorator } from '@storybook/react';

export interface RouterParameter {
	/** The history the story starts with; the last entry is the current location. Default `['/']`. */
	initialEntries?: React.ComponentProps<typeof MemoryRouter>['initialEntries'];
	/** A route pattern such as `/kanji/:kanji_id`, so `useParams` reads the entry's params. */
	path?: string;
}

let latestLocation = '/';

/** Where the story's router is now (`pathname` + `search`), so a `play` can assert a navigation. */
export const currentLocation = () => latestLocation;

const LocationTracker: React.FC = () => {
	const { pathname, search } = useLocation();
	latestLocation = `${pathname}${search}`;

	return null;
};

/** Provides an in-memory router from `parameters.router`, so stories never touch the iframe URL. */
export const withRouter: Decorator = (Story, { id, parameters }) => {
	const { initialEntries = ['/'], path } = (parameters.router as RouterParameter | undefined) ?? {};

	return (
		<MemoryRouter key={id} initialEntries={initialEntries}>
			<LocationTracker />
			{path ? (
				<Routes>
					<Route path={path} element={<Story />} />
				</Routes>
			) : (
				<Story />
			)}
		</MemoryRouter>
	);
};
