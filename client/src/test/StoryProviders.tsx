import * as React from 'react';
import { AuthContext } from '@/providers/contexts/auth-provider';
import { DEFAULT_SOCKET_CONTEXT, SocketContext } from '@/providers/contexts/socket-provider';
import { SeededQueryClient, type QuerySeed } from './seededQueryClient';
import { guestAuth, type AuthValue } from './storyAuth';

/** What a page story sets through `parameters`: who is looking, and what the cache holds. */
export interface PageStoryParameters {
	auth?: AuthValue;
	seeds?: QuerySeed[];
}

/**
 * The app's providers for a page story: the viewer (a guest unless given), a connected socket so
 * comments render as they do live, and a query cache seeded with the page's lists.
 */
export const StoryProviders: React.FC<PageStoryParameters & { children: React.ReactNode }> = ({
	auth = guestAuth,
	seeds = [],
	children,
}) => (
	<AuthContext.Provider value={auth}>
		<SocketContext.Provider value={{ ...DEFAULT_SOCKET_CONTEXT, isConnected: true, connectionStatus: 'connected' }}>
			<SeededQueryClient seeds={seeds}>{children}</SeededQueryClient>
		</SocketContext.Provider>
	</AuthContext.Provider>
);
