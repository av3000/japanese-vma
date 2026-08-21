import type { KanjiIndex200ItemsItem } from '@/api/generated/model/kanjiIndex200ItemsItem';
import type { KanjiDetailResource } from '@/api/generated/model/kanjiDetailResource';
import type { KanjiResource } from '@/api/generated/model/kanjiResource';

// TOOD: Remove wrappers with http codes on the backend to have clear response on orval generation
type DisplayableKanji = KanjiIndex200ItemsItem | KanjiDetailResource | KanjiResource;

const joinLimited = (values: string[] | undefined, limit = 3) => (values ?? []).slice(0, limit).join(', ');

export const getKanjiDisplayValues = (kanji: DisplayableKanji) => ({
	meaning: joinLimited(kanji.meanings),
	onyomi: joinLimited(kanji.onyomi),
	kunyomi: joinLimited(kanji.kunyomi),
	nanori: joinLimited(kanji.nanori),
	radicals: joinLimited(kanji.radicals),
	radicalParts: joinLimited(kanji.radical_parts),
	grade: kanji.grade ?? '',
	jlpt: kanji.jlpt ?? '',
	frequency: kanji.frequency === null ? '' : String(kanji.frequency),
});
