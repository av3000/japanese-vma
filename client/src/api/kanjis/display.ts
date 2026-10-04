import type { KanjiDetailResource } from '@/api/generated/model/kanjiDetailResource';
import type { KanjiResource } from '@/api/generated/model/kanjiResource';

type DisplayableKanji = KanjiResource | KanjiDetailResource;

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
