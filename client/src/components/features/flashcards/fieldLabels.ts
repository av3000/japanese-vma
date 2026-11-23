import { FlashcardField } from '@/api/generated/model/flashcardField';
import type { StudyFamily } from '@/shared/constants/catalogues';

/**
 * What the flashcard UI calls each field, in one place: the setup form's selects, the typed
 * answer's label and the not-typeable hint all read from here, so they cannot drift apart.
 */

const CHARACTER_LABEL: Record<StudyFamily, string> = {
	kanji: 'Kanji',
	words: 'Word',
	radicals: 'Radical',
};

export const flashcardFieldLabel = (field: FlashcardField, family: StudyFamily): string => {
	switch (field) {
		case FlashcardField.character:
			return CHARACTER_LABEL[family];
		case FlashcardField.meaning:
			return 'Meaning (English)';
		case FlashcardField.onyomi:
			return 'On’yomi (katakana)';
		case FlashcardField.kunyomi:
			return 'Kun’yomi (hiragana)';
		case FlashcardField.reading:
			return family === 'radicals' ? 'Reading (kana or romaji)' : 'Reading (kana)';
	}
};

/** Every field but the English meaning is Japanese text: `lang="ja"` and the Japanese font. */
export const isJapaneseField = (field: FlashcardField): boolean => field !== FlashcardField.meaning;
