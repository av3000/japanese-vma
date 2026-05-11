import { z } from 'zod';
import { isCustomCatalogueType } from '@/shared/constants/catalogues';

export const MAX_CATALOGUE_TITLE_LENGTH = 255;
export const MAX_CATALOGUE_TAG_LENGTH = 50;
export const MAX_CATALOGUE_TAGS = 10;

export const buildCatalogueFormSchema = () => {
	return z.object({
		title: z
			.string()
			.trim()
			.min(2, 'The catalogue title must be at least 2 characters')
			.max(MAX_CATALOGUE_TITLE_LENGTH, 'The catalogue title must not exceed 255 characters'),
		type: z.number().refine(isCustomCatalogueType, 'Please choose a supported catalogue type'),
		publicity: z.boolean(),
		tags: z.array(z.string().trim().min(1).max(MAX_CATALOGUE_TAG_LENGTH)).max(MAX_CATALOGUE_TAGS),
	});
};

export type CatalogueFormValues = z.infer<ReturnType<typeof buildCatalogueFormSchema>>;
