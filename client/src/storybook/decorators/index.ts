import type { AppearanceParameter } from './withAppearance';
import type { AuthParameter } from './withAuth';
import type { QuerySeed } from './withQueryClient';
import type { RouterParameter } from './withRouter';
import type { SocketParameter } from './withSocket';
import type { TranslationParameter } from './withTranslation';

export * from './withAppearance';
export * from './withAuth';
export * from './withQueryClient';
export * from './withRouter';
export * from './withSocket';
export * from './withTranslation';

/** The parameters the global decorators read. Docs/Authoring explains each one. */
export interface AppStoryParameters {
	appearance?: AppearanceParameter;
	auth?: AuthParameter;
	socket?: SocketParameter;
	translation?: TranslationParameter;
	router?: RouterParameter;
	seeds?: QuerySeed[];
}
