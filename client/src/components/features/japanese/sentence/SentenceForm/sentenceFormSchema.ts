import { z } from 'zod';

// Mirrors StoreSentenceRequest/UpdateSentenceRequest: required|string|min:4|max:300.
export const MIN_SENTENCE_LENGTH = 4;
export const MAX_SENTENCE_LENGTH = 300;

// The server trims in `prepareForValidation()` before it validates, so the client
// has to trim before measuring too. Without it a padded 3-character value passes
// here and then comes back as a 422.
export const sentenceFormSchema = z.object({
	content: z
		.string()
		.trim()
		.min(1, 'Sentence is required.')
		.pipe(z.string().min(MIN_SENTENCE_LENGTH, `Sentence must be at least ${MIN_SENTENCE_LENGTH} characters.`))
		.pipe(z.string().max(MAX_SENTENCE_LENGTH, `Sentence must be at most ${MAX_SENTENCE_LENGTH} characters.`)),
});

export type SentenceFormValues = z.infer<typeof sentenceFormSchema>;

export const buildSentenceWritePayload = (values: SentenceFormValues) => ({
	content: values.content.trim(),
});
