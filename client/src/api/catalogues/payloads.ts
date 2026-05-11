import type { StoreCatalogueRequest } from '@/api/generated/model/storeCatalogueRequest';
import type { UpdateCatalogueRequest } from '@/api/generated/model/updateCatalogueRequest';
import type { CatalogueFormValues } from '@/components/features/catalogues/CatalogueForm';
import { stringifyCatalogueTags } from './legacyCatalogues';

type CatalogueFormField = Extract<keyof CatalogueFormValues, string>;

// TODO: Wonder if these builders should be here or closer to the actual forms and together with form schemas like buildCatalogueFormSchema.
// Also not sure about the naming, need to revisit to unify these.
// TODO: Implement same builders for Article detail/edit routes
export const buildCreateCataloguePayload = (values: CatalogueFormValues): StoreCatalogueRequest => ({
	title: values.title.trim(),
	type: values.type as StoreCatalogueRequest['type'],
	publicity: values.publicity,
	tags: stringifyCatalogueTags(values.tags),
});

export const buildUpdateCataloguePayload = (
	values: CatalogueFormValues,
	dirtyKeys: CatalogueFormField[],
): UpdateCatalogueRequest => {
	const payload: UpdateCatalogueRequest = {};

	if (dirtyKeys.includes('title')) {
		payload.title = values.title.trim();
	}
	if (dirtyKeys.includes('type')) {
		payload.type = values.type as UpdateCatalogueRequest['type'];
	}
	if (dirtyKeys.includes('publicity')) {
		payload.publicity = values.publicity;
	}
	if (dirtyKeys.includes('tags')) {
		payload.tags = stringifyCatalogueTags(values.tags);
	}

	return payload;
};
