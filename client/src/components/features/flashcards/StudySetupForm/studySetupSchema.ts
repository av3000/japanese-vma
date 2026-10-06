import { z } from 'zod';
import { STUDY_MAX_COUNT, STUDY_MIN_COUNT, isValidStudyCombination } from '@/api/flashcards/deck';
import { AnswerMode } from '@/api/generated/model/answerMode';
import { FlashcardField } from '@/api/generated/model/flashcardField';

const fieldEnum = z.enum([
	FlashcardField.character,
	FlashcardField.meaning,
	FlashcardField.onyomi,
	FlashcardField.kunyomi,
	FlashcardField.reading,
]);

/**
 * The setup form's values. `lenient` is the checkbox behind `script`; the form maps it to
 * the enum on the way out so the URL and the API see `strict` / `lenient`.
 */
export const buildStudySetupSchema = (catalogueType: number) =>
	z
		.object({
			prompt: fieldEnum,
			answer: fieldEnum,
			mode: z.enum([AnswerMode.options, AnswerMode.typed]),
			lenient: z.boolean(),
			count: z
				.number({ invalid_type_error: 'Enter how many cards to study' })
				.int('Enter a whole number of cards')
				.min(STUDY_MIN_COUNT, `Study at least ${STUDY_MIN_COUNT} card`)
				.max(STUDY_MAX_COUNT, `Study at most ${STUDY_MAX_COUNT} cards per run`),
		})
		.refine((values) => isValidStudyCombination(catalogueType, values.prompt, values.answer, values.mode), {
			message: 'This catalogue cannot be studied with that combination',
			path: ['answer'],
		});

export type StudySetupValues = z.infer<ReturnType<typeof buildStudySetupSchema>>;
