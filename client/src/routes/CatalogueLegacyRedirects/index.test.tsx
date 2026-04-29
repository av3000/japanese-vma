import { describe, expect, it } from 'vitest';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';
import { getCatalogueLegacyRedirectVariant } from './index';

describe('getCatalogueLegacyRedirectVariant', () => {
	it('classifies legacy catalogue aliases as redirect-only routes', () => {
		expect(getCatalogueLegacyRedirectVariant(CATALOGUE_ROUTES.legacyList)).toBe('list');
		expect(getCatalogueLegacyRedirectVariant(CATALOGUE_ROUTES.legacyCreate)).toBe('create');
		expect(getCatalogueLegacyRedirectVariant(CATALOGUE_ROUTES.legacyEdit('42'))).toBe('edit');
		expect(getCatalogueLegacyRedirectVariant(CATALOGUE_ROUTES.legacyDetail('42'))).toBe('detail');
	});
});
