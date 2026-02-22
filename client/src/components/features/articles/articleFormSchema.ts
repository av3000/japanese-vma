import { z } from 'zod';
import { isArrayUnique, isValidHttpUrl } from '@/helpers';

export const MAX_TITLE_LENGTH = 255;
export const MIN_TITLE_LENGTH = 2;
export const MAX_CONTENT_LENGTH = 2000;
export const MIN_CONTENT_LENGTH = 10;
export const MAX_SOURCE_LINK_LENGTH = 500;
export const MAX_TAG_LENGTH = 50;
export const MAX_TAG_QUANTITY = 10;

const requiredWithMinMax = (opts: {
	requiredMessage: string;
	min: number;
	minMessage: string;
	max: number;
	maxMessage: string;
}) => {
	return z
		.string()
		.trim()
		.min(1, opts.requiredMessage)
		.pipe(z.string().min(opts.min, opts.requiredMessage))
		.pipe(z.string().max(opts.max, opts.requiredMessage));
};

const optionalWithMinMax = (opts: { min: number; minMessage: string; max: number; maxMessage: string }) => {
	return z
		.string()
		.trim()
		.pipe(z.string().max(opts.max, opts.maxMessage))
		.pipe(
			z.string().refine((value) => value === '' || value.length >= opts.min, {
				message: opts.minMessage,
			}),
		);
};

const articleFormValuesSchema = z.object({
	title_jp: z.string(),
	title_en: z.string(),
	content_jp: z.string(),
	content_en: z.string(),
	source_link: z.string(),
	publicity: z.boolean(),
	tags: z.array(z.string()),
});

export type ArticleFormValues = z.infer<typeof articleFormValuesSchema>;

export function buildArticleFormSchema({ requireEnglishTitle }: { requireEnglishTitle: boolean }) {
	const titleEnSchema = requireEnglishTitle
		? requiredWithMinMax({
				requiredMessage: 'English title is required.',
				min: MIN_TITLE_LENGTH,
				minMessage: `English title must be at least ${MIN_TITLE_LENGTH} characters.`,
				max: MAX_TITLE_LENGTH,
				maxMessage: `English title must be at most ${MAX_TITLE_LENGTH} characters.`,
			})
		: optionalWithMinMax({
				min: MIN_TITLE_LENGTH,
				minMessage: `English title must be at least ${MIN_TITLE_LENGTH} characters.`,
				max: MAX_TITLE_LENGTH,
				maxMessage: `English title must be at most ${MAX_TITLE_LENGTH} characters.`,
			});

	const tagsSchema = z
		.array(z.string().trim())
		.max(MAX_TAG_QUANTITY, `Maximum ${MAX_TAG_QUANTITY} tags allowed.`)
		.superRefine((tags, ctx) => {
			if (tags.length > MAX_TAG_QUANTITY) {
				return;
			}

			if (tags.some((tag) => tag.length === 0)) {
				ctx.addIssue({
					code: z.ZodIssueCode.custom,
					path: [],
					message: 'Tags cannot be empty.',
				});
				return;
			}

			if (tags.some((tag) => tag.length > MAX_TAG_LENGTH)) {
				ctx.addIssue({
					code: z.ZodIssueCode.custom,
					path: [],
					message: `Each tag must not exceed ${MAX_TAG_LENGTH} characters.`,
				});
				return;
			}

			if (!isArrayUnique(tags)) {
				ctx.addIssue({
					code: z.ZodIssueCode.custom,
					path: [],
					message: 'Duplicate tags are not allowed.',
				});
			}
		});

	return z.object({
		title_jp: requiredWithMinMax({
			requiredMessage: 'Japanese title is required.',
			min: MIN_TITLE_LENGTH,
			minMessage: `Japanese title must be at least ${MIN_TITLE_LENGTH} characters.`,
			max: MAX_TITLE_LENGTH,
			maxMessage: `Japanese title must be at most ${MAX_TITLE_LENGTH} characters.`,
		}),
		title_en: titleEnSchema,
		content_jp: requiredWithMinMax({
			requiredMessage: 'Japanese content is required.',
			min: MIN_CONTENT_LENGTH,
			minMessage: `Japanese content must be at least ${MIN_CONTENT_LENGTH} characters.`,
			max: MAX_CONTENT_LENGTH,
			maxMessage: 'Japanese content must be at most 2000 characters.',
		}),
		content_en: optionalWithMinMax({
			min: MIN_CONTENT_LENGTH,
			minMessage: `English content must be at least ${MIN_CONTENT_LENGTH} characters.`,
			max: MAX_CONTENT_LENGTH,
			maxMessage: `English content must be at most ${MAX_CONTENT_LENGTH} characters.`,
		}),
		source_link: z
			.string()
			.trim()
			.min(1, 'Source link is required.')
			.pipe(
				z
					.string()
					.max(MAX_SOURCE_LINK_LENGTH, `Source link must be at most ${MAX_SOURCE_LINK_LENGTH} characters.`),
			)
			.pipe(
				z.string().refine(isValidHttpUrl, {
					message: 'Source link must be a valid http(s) URL.',
				}),
			),
		publicity: z.boolean(),
		tags: tagsSchema,
	}) satisfies z.ZodType<ArticleFormValues>;
}
