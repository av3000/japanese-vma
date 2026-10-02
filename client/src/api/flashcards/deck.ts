import { useFlashcardDeckShow, getFlashcardDeckShowQueryKey } from '@/api/generated/flashcard-deck/flashcard-deck';
import { AnswerMode } from '@/api/generated/model/answerMode';
import type { FlashcardDeckResource } from '@/api/generated/model/flashcardDeckResource';
import type { FlashcardDeckShowParams } from '@/api/generated/model/flashcardDeckShowParams';
import { FlashcardField } from '@/api/generated/model/flashcardField';
import type { FlashcardResource } from '@/api/generated/model/flashcardResource';
import { ScriptStrictness } from '@/api/generated/model/scriptStrictness';
import { SavedListType } from '@/shared/constants/enums';

/**
 * Deck access for the Study route (epic #413). The configuration lives in the URL so a
 * specific drill is a link; this module parses it, serialises it, and knows which fields a
 * catalogue type allows. The allowed-fields table mirrors the backend's
 * `FlashcardConfig::fieldsFor` and must change together with it.
 */

export const STUDY_DEFAULT_COUNT = 20;
export const STUDY_MIN_COUNT = 1;
export const STUDY_MAX_COUNT = 100;
export const STUDY_MAX_SEED = 2147483647;

export interface StudyConfig {
	prompt: FlashcardField;
	answer: FlashcardField;
	mode: AnswerMode;
	script: ScriptStrictness;
	count: number;
	seed?: number;
}

export type StudyFamily = 'kanji' | 'words' | 'radicals';

/** Catalogue type → the family its cards are built from; null for types that cannot be studied. */
export const studyFamilyFor = (catalogueType: number): StudyFamily | null => {
	switch (catalogueType) {
		case SavedListType.KANJIS:
		case SavedListType.KNOWNKANJIS:
			return 'kanji';
		case SavedListType.WORDS:
		case SavedListType.KNOWNWORDS:
			return 'words';
		case SavedListType.RADICALS:
		case SavedListType.KNOWNRADICALS:
			return 'radicals';
		default:
			return null;
	}
};

const FIELDS_BY_FAMILY: Record<StudyFamily, readonly FlashcardField[]> = {
	kanji: [FlashcardField.character, FlashcardField.meaning, FlashcardField.onyomi, FlashcardField.kunyomi],
	words: [FlashcardField.character, FlashcardField.meaning, FlashcardField.reading],
	radicals: [FlashcardField.character, FlashcardField.meaning, FlashcardField.reading],
};

export const studyFieldsFor = (catalogueType: number): readonly FlashcardField[] => {
	const family = studyFamilyFor(catalogueType);
	return family ? FIELDS_BY_FAMILY[family] : [];
};

/** A character answer needs an IME round trip the typed mode cannot grade fairly. */
export const isTypeableField = (field: FlashcardField): boolean => field !== FlashcardField.character;

export const isValidStudyCombination = (
	catalogueType: number,
	prompt: FlashcardField,
	answer: FlashcardField,
	mode: AnswerMode,
): boolean => {
	const fields = studyFieldsFor(catalogueType);

	if (prompt === answer || !fields.includes(prompt) || !fields.includes(answer)) {
		return false;
	}

	if (prompt !== FlashcardField.character && answer !== FlashcardField.character) {
		return false;
	}

	return mode === AnswerMode.options || isTypeableField(answer);
};

export const defaultStudyConfig = (): StudyConfig => ({
	prompt: FlashcardField.character,
	answer: FlashcardField.meaning,
	mode: AnswerMode.options,
	script: ScriptStrictness.strict,
	count: STUDY_DEFAULT_COUNT,
});

const isEnumValue = <T extends Record<string, string>>(table: T, value: string | null): value is T[keyof T] =>
	value !== null && Object.values(table).includes(value);

const parseBoundedInt = (value: string | null, min: number, max: number): number | undefined => {
	if (value === null || !/^\d+$/.test(value)) {
		return undefined;
	}

	const parsed = Number(value);
	return parsed >= min && parsed <= max ? parsed : undefined;
};

/**
 * Reads the config from the URL. Anything invalid falls back to the default, and a field
 * combination the catalogue type does not allow falls back to the default combination,
 * so a mistyped link still opens a working setup form.
 */
export const parseStudyConfig = (searchParams: URLSearchParams, catalogueType?: number): StudyConfig => {
	const defaults = defaultStudyConfig();
	const prompt = searchParams.get('prompt');
	const answer = searchParams.get('answer');
	const mode = searchParams.get('mode');
	const script = searchParams.get('script');

	const config: StudyConfig = {
		prompt: isEnumValue(FlashcardField, prompt) ? prompt : defaults.prompt,
		answer: isEnumValue(FlashcardField, answer) ? answer : defaults.answer,
		mode: isEnumValue(AnswerMode, mode) ? mode : defaults.mode,
		script: isEnumValue(ScriptStrictness, script) ? script : defaults.script,
		count: parseBoundedInt(searchParams.get('count'), STUDY_MIN_COUNT, STUDY_MAX_COUNT) ?? defaults.count,
	};

	const seed = parseBoundedInt(searchParams.get('seed'), 0, STUDY_MAX_SEED);
	if (seed !== undefined) {
		config.seed = seed;
	}

	if (
		catalogueType !== undefined &&
		!isValidStudyCombination(catalogueType, config.prompt, config.answer, config.mode)
	) {
		return { ...config, prompt: defaults.prompt, answer: defaults.answer, mode: defaults.mode };
	}

	return config;
};

/** Writes the config to URL params; defaults are written too, so a shared link is explicit. */
export const studyConfigToSearchParams = (config: StudyConfig, extra: Record<string, string> = {}): URLSearchParams => {
	const params = new URLSearchParams();
	params.set('prompt', config.prompt);
	params.set('answer', config.answer);
	params.set('mode', config.mode);
	params.set('script', config.script);
	params.set('count', String(config.count));

	if (config.seed !== undefined) {
		params.set('seed', String(config.seed));
	}

	for (const [key, value] of Object.entries(extra)) {
		params.set(key, value);
	}

	return params;
};

export const studyConfigToParams = (config: StudyConfig): FlashcardDeckShowParams => ({
	prompt: config.prompt,
	answer: config.answer,
	mode: config.mode,
	script: config.script,
	count: config.count,
	...(config.seed !== undefined ? { seed: config.seed } : {}),
});

/* Deck ------------------------------------------------------------------- */

export interface StudyCard {
	itemId: number;
	itemUuid: string;
	promptText: string;
	promptHint: string | null;
	acceptedAnswers: string[];
	displayAnswer: string;
	options: string[] | null;
	jlpt: string | null;
	grade: string | null;
	strokes: number | null;
}

export interface StudyDeck {
	catalogue: FlashcardDeckResource['catalogue'];
	config: StudyConfig;
	cards: StudyCard[];
	totalItems: number;
	eligibleItems: number;
	excludedEmptyAnswerField: number;
}

export const mapStudyCard = (card: FlashcardResource): StudyCard => ({
	itemId: card.item_id,
	itemUuid: card.item_uuid,
	promptText: card.prompt.text,
	promptHint: card.prompt.hint ?? null,
	acceptedAnswers: card.accepted_answers,
	displayAnswer: card.display_answer,
	options: card.options ?? null,
	jlpt: card.meta.jlpt ?? null,
	grade: card.meta.grade ?? null,
	strokes: card.meta.strokes ?? null,
});

export const mapStudyDeck = (deck: FlashcardDeckResource): StudyDeck => ({
	catalogue: deck.catalogue,
	config: {
		prompt: deck.config.prompt,
		answer: deck.config.answer,
		mode: deck.config.mode,
		script: deck.config.script,
		count: deck.config.count,
		seed: deck.config.seed,
	},
	cards: deck.cards.map(mapStudyCard),
	totalItems: deck.total_items,
	eligibleItems: deck.eligible_items,
	excludedEmptyAnswerField: deck.excluded.empty_answer_field,
});

export const getStudyDeckQueryKey = (catalogueUuid: string, config: StudyConfig) =>
	getFlashcardDeckShowQueryKey(catalogueUuid, studyConfigToParams(config));

export const useStudyDeck = (catalogueUuid: string | undefined, config: StudyConfig, enabled = true) =>
	useFlashcardDeckShow<StudyDeck>(catalogueUuid ?? '', studyConfigToParams(config), {
		query: {
			enabled: Boolean(catalogueUuid) && enabled,
			retry: false,
			// A deck is a pure function of catalogue and config; keep it while the learner plays.
			staleTime: 5 * 60 * 1000,
			select: mapStudyDeck,
		},
	});

/* Failures --------------------------------------------------------------- */

export interface DeckFailure {
	status: number | null;
	/** Problem Details `title`, e.g. "No eligible cards". */
	title: string;
	detail: string | null;
}

/** Reads the backend's Problem Details from a failed deck request. */
export const readDeckFailure = (error: unknown): DeckFailure => {
	const response = (error as { response?: { status?: number; data?: unknown } } | undefined)?.response;
	const data = (response?.data ?? null) as { title?: string; detail?: string; status?: number } | null;

	return {
		status: data?.status ?? response?.status ?? null,
		title: data?.title ?? 'The deck could not be loaded',
		detail: data?.detail ?? null,
	};
};
