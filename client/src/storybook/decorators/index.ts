import type { AuthParameter } from './withAuth';
import type { QuerySeed } from './withQueryClient';
import type { RouterParameter } from './withRouter';
import type { SocketParameter } from './withSocket';

export * from './withAuth';
export * from './withQueryClient';
export * from './withRouter';
export * from './withSocket';

/** The parameters the global decorators read. Docs/Authoring explains each one. */
export interface AppStoryParameters {
	auth?: AuthParameter;
	socket?: SocketParameter;
	router?: RouterParameter;
	seeds?: QuerySeed[];
}
