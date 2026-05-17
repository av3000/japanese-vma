import { describe, expect, it } from 'vitest';
import {
	CATALOGUE_ROUTES,
	CATALOGUE_TYPE_LABELS,
	isCataloguePdfExportSupported,
	isCatalogueRouteUuid,
	isCustomCatalogueType,
	resolveCataloguePdfExportKind,
	resolveCatalogueTypeLabel,
} from './catalogues';

describe('catalogue constants', () => {
	it('defines canonical routes and legacy aliases for the migration window', () => {
		expect(CATALOGUE_ROUTES.list).toBe('/catalogues');
		expect(CATALOGUE_ROUTES.detail(':catalogueId')).toBe('/catalogues/:catalogueId');
		expect(CATALOGUE_ROUTES.create).toBe('/catalogues/new');
		expect(CATALOGUE_ROUTES.edit(':catalogueId')).toBe('/catalogues/:catalogueId/edit');
		expect(CATALOGUE_ROUTES.legacyList).toBe('/lists');
		expect(CATALOGUE_ROUTES.legacyDetail(':catalogueId')).toBe('/list/:catalogueId');
		expect(CATALOGUE_ROUTES.legacyCreate).toBe('/newlist');
		expect(CATALOGUE_ROUTES.legacyEdit(':catalogueId')).toBe('/list/edit/:catalogueId');
	});

	it('maps supported custom catalogue types to stable labels', () => {
		expect(CATALOGUE_TYPE_LABELS[5]).toBe('Radicals');
		expect(CATALOGUE_TYPE_LABELS[6]).toBe('Kanjis');
		expect(CATALOGUE_TYPE_LABELS[7]).toBe('Words');
		expect(CATALOGUE_TYPE_LABELS[8]).toBe('Sentences');
		expect(CATALOGUE_TYPE_LABELS[9]).toBe('Articles');
		expect(resolveCatalogueTypeLabel(7)).toBe('Words');
		expect(resolveCatalogueTypeLabel(999)).toBe('Unknown');
	});

	it('distinguishes custom catalogue numeric types from unrelated values', () => {
		expect(isCustomCatalogueType(5)).toBe(true);
		expect(isCustomCatalogueType(9)).toBe(true);
		expect(isCustomCatalogueType(4)).toBe(false);
		expect(isCustomCatalogueType(20)).toBe(false);
	});

	it('maps v1 catalogue PDF exports to supported catalogue types', () => {
		expect(resolveCataloguePdfExportKind(2)).toBe('kanji');
		expect(resolveCataloguePdfExportKind(6)).toBe('kanji');
		expect(resolveCataloguePdfExportKind(3)).toBe('words');
		expect(resolveCataloguePdfExportKind(7)).toBe('words');
		expect(resolveCataloguePdfExportKind(5)).toBeNull();
		expect(resolveCataloguePdfExportKind(8)).toBeNull();
		expect(resolveCataloguePdfExportKind(9)).toBeNull();
		expect(isCataloguePdfExportSupported(6)).toBe(true);
		expect(isCataloguePdfExportSupported(9)).toBe(false);
	});

	it('treats UUID-like params as canonical identifiers and numeric params as legacy aliases', () => {
		expect(isCatalogueRouteUuid('86f593b4-3f77-44fe-8d42-d8e993f7850b')).toBe(true);
		expect(isCatalogueRouteUuid('42')).toBe(false);
		expect(isCatalogueRouteUuid('not-a-uuid')).toBe(false);
	});
});
