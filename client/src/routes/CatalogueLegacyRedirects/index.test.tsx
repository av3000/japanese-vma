import { describe, expect, it } from 'vitest';
import { CATALOGUE_ROUTES } from '@/shared/constants/catalogues';
import { getCatalogueLegacyRedirectVariant, resolveCatalogueLegacyTarget } from './index';

const identity = { id: 42, uuid: '86f593b4-3f77-44fe-8d42-d8e993f7850b' };

describe('getCatalogueLegacyRedirectVariant', () => {
	it('classifies legacy catalogue aliases as redirect-only routes', () => {
		expect(getCatalogueLegacyRedirectVariant(CATALOGUE_ROUTES.legacyList)).toBe('list');
		expect(getCatalogueLegacyRedirectVariant(CATALOGUE_ROUTES.legacyCreate)).toBe('create');
		expect(getCatalogueLegacyRedirectVariant(CATALOGUE_ROUTES.legacyEdit('42'))).toBe('edit');
		expect(getCatalogueLegacyRedirectVariant(CATALOGUE_ROUTES.legacyDetail('42'))).toBe('detail');
	});
});

describe('resolveCatalogueLegacyTarget', () => {
	it('sends a resolved detail id to the canonical UUID detail route', () => {
		expect(
			resolveCatalogueLegacyTarget({
				legacyId: 42,
				identity,
				isError: false,
				to: CATALOGUE_ROUTES.detail,
			}),
		).toEqual({ status: 'redirect', to: CATALOGUE_ROUTES.detail(identity.uuid) });
	});

	it('sends a resolved edit id to the canonical UUID edit route', () => {
		expect(
			resolveCatalogueLegacyTarget({
				legacyId: 42,
				identity,
				isError: false,
				to: CATALOGUE_ROUTES.edit,
			}),
		).toEqual({ status: 'redirect', to: CATALOGUE_ROUTES.edit(identity.uuid) });
	});

	it('waits while a supported id is still resolving', () => {
		expect(
			resolveCatalogueLegacyTarget({
				legacyId: 42,
				identity: undefined,
				isError: false,
				to: CATALOGUE_ROUTES.detail,
			}),
		).toEqual({ status: 'pending' });
	});

	it('fails an id the backend route constraint would reject, without waiting on a request', () => {
		expect(
			resolveCatalogueLegacyTarget({
				legacyId: null,
				identity: undefined,
				isError: false,
				to: CATALOGUE_ROUTES.detail,
			}),
		).toEqual({ status: 'failed' });
	});

	it('fails a missing or inaccessible catalogue the same way', () => {
		expect(
			resolveCatalogueLegacyTarget({
				legacyId: 42,
				identity: undefined,
				isError: true,
				to: CATALOGUE_ROUTES.detail,
			}),
		).toEqual({ status: 'failed' });
	});
});
