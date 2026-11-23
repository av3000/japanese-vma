import { z } from 'zod';
import type { StorePostRequest } from '@/api/generated/model/storePostRequest';
import type { UpdatePostRequest } from '@/api/generated/model/updatePostRequest';
import { isPostTopic } from '@/api/posts/reads';

// Mirrors StorePostRequest/UpdatePostRequest.
export const MIN_TITLE_LENGTH = 2;
export const MAX_TITLE_LENGTH = 255;
export const MIN_CONTENT_LENGTH = 5;
export const MAX_CONTENT_LENGTH = 15000;
export const MAX_TAG_LENGTH = 50;
export const MAX_TAG_QUANTITY = 10;

/**
 * The server trims title and content in `prepareForValidation()` before it measures them, so the
 * client trims before measuring too. Without it a padded 4-character body passes here and comes
 * back as a 422 the reader cannot act on.
 */
const trimmedBounded = (opts: {
	requiredMessage: string;
	min: number;
	minMessage: string;
	max: number;
	maxMessage: string;
}) =>
	z
		.string()
		.trim()
		.min(1, opts.requiredMessage)
		.pipe(z.string().min(opts.min, opts.minMessage))
		.pipe(z.string().max(opts.max, opts.maxMessage));

const tagsSchema = z
	.array(z.string().trim())
	.max(MAX_TAG_QUANTITY, `Maximum ${MAX_TAG_QUANTITY} tags allowed.`)
	.superRefine((tags, ctx) => {
		if (tags.some((tag) => tag.length === 0)) {
			ctx.addIssue({ code: z.ZodIssueCode.custom, message: 'Tags cannot be empty.' });
		}

		if (tags.some((tag) => tag.length > MAX_TAG_LENGTH)) {
			ctx.addIssue({
				code: z.ZodIssueCode.custom,
				message: `Each tag must be at most ${MAX_TAG_LENGTH} characters.`,
			});
		}

		// `tags.*.distinct` on the server. Comparison is case-sensitive to match Laravel's default.
		if (new Set(tags).size !== tags.length) {
			ctx.addIssue({ code: z.ZodIssueCode.custom, message: 'Duplicate tags are not allowed.' });
		}
	});

export const postFormSchema = z.object({
	title: trimmedBounded({
		requiredMessage: 'Title is required.',
		min: MIN_TITLE_LENGTH,
		minMessage: `Title must be at least ${MIN_TITLE_LENGTH} characters.`,
		max: MAX_TITLE_LENGTH,
		maxMessage: `Title must be at most ${MAX_TITLE_LENGTH} characters.`,
	}),
	content: trimmedBounded({
		requiredMessage: 'Content is required.',
		min: MIN_CONTENT_LENGTH,
		minMessage: `Content must be at least ${MIN_CONTENT_LENGTH} characters.`,
		max: MAX_CONTENT_LENGTH,
		maxMessage: `Content must be at most ${MAX_CONTENT_LENGTH} characters.`,
	}),
	topic: z.number().refine(isPostTopic, 'Topic must be one of the supported post topics.'),
	tags: tagsSchema,
});

export type PostFormValues = z.infer<typeof postFormSchema>;

export type PostFormField = keyof PostFormValues;

export const POST_FORM_FIELDS: PostFormField[] = ['title', 'content', 'topic', 'tags'];

export const isPostFormField = (field: string): field is PostFormField =>
	(POST_FORM_FIELDS as string[]).includes(field);

export const buildPostCreatePayload = (values: PostFormValues): StorePostRequest => ({
	title: values.title.trim(),
	content: values.content.trim(),
	topic: values.topic,
	tags: values.tags.map((tag) => tag.trim()),
});

/**
 * `UpdatePostRequest` is a partial and rejects an empty body, so only the fields the author
 * actually touched are sent. Sending everything would overwrite a field a second editor changed
 * between load and submit.
 */
export const buildPostUpdatePayload = (values: PostFormValues, dirtyKeys: PostFormField[]): UpdatePostRequest => {
	const payload: UpdatePostRequest = {};

	if (dirtyKeys.includes('title')) payload.title = values.title.trim();
	if (dirtyKeys.includes('content')) payload.content = values.content.trim();
	if (dirtyKeys.includes('topic')) payload.topic = values.topic;
	// An empty array is a real value here: it clears every tag.
	if (dirtyKeys.includes('tags')) payload.tags = values.tags.map((tag) => tag.trim());

	return payload;
};
