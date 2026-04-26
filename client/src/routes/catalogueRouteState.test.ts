import { describe, expect, it } from 'vitest';
import { getCatalogueRouteState } from './catalogueRouteState';

describe('getCatalogueRouteState', () => {
	it('keeps canonical UUID params and skips legacy resolution', () => {
		expect(getCatalogueRouteState('86f593b4-3f77-44fe-8d42-d8e993f7850b')).toEqual({
			hasUuidParam: true,
			resolvedUuid: '86f593b4-3f77-44fe-8d42-d8e993f7850b',
			shouldResolveLegacyIdentity: false,
		});
	});

	it('uses the resolved legacy UUID for numeric /list aliases', () => {
		expect(getCatalogueRouteState('42', '86f593b4-3f77-44fe-8d42-d8e993f7850b')).toEqual({
			hasUuidParam: false,
			resolvedUuid: '86f593b4-3f77-44fe-8d42-d8e993f7850b',
			shouldResolveLegacyIdentity: true,
		});
	});
});
