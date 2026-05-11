export const CATALOGUE_TYPE_LABELS = {
	5: 'Radicals',
	6: 'Kanjis',
	7: 'Words',
	8: 'Sentences',
	9: 'Articles',
} as const;

export type CustomCatalogueType = keyof typeof CATALOGUE_TYPE_LABELS;

export const CATALOGUE_TYPE_OPTIONS = Object.entries(CATALOGUE_TYPE_LABELS).map(([value, label]) => ({
	value: Number(value) as CustomCatalogueType,
	label,
}));

export const CATALOGUE_ROUTES = {
	list: '/catalogues',
	detail: (catalogueId: string) => `/catalogues/${catalogueId}`,
	create: '/catalogues/new',
	edit: (catalogueId: string) => `/catalogues/${catalogueId}/edit`,
	legacyList: '/lists',
	legacyDetail: (catalogueId: string) => `/list/${catalogueId}`,
	legacyCreate: '/newlist',
	legacyEdit: (catalogueId: string) => `/list/edit/${catalogueId}`,
} as const;

const CATALOGUE_UUID_PATTERN =
	/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i;

export const isCustomCatalogueType = (value: number): value is CustomCatalogueType => {
	return Object.prototype.hasOwnProperty.call(CATALOGUE_TYPE_LABELS, value);
};

export const resolveCatalogueTypeLabel = (value: number) => {
	return isCustomCatalogueType(value) ? CATALOGUE_TYPE_LABELS[value] : 'Unknown';
};

export const isCatalogueRouteUuid = (value: string) => {
	return CATALOGUE_UUID_PATTERN.test(value);
};
