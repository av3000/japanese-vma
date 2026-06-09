import { useQuery } from '@tanstack/react-query';
import { getKanjiShowQueryKey, kanjiShow } from '@/api/generated/kanji/kanji';
import type { KanjiShow200 } from '@/api/generated/model/kanjiShow200';
import { getKanjiDisplayValues } from './display';

export interface MappedKanji extends KanjiShow200 {
	display: ReturnType<typeof getKanjiDisplayValues>;
}

export const mapKanjiDetail = (kanji: KanjiShow200): MappedKanji => ({
	...kanji,
	display: getKanjiDisplayValues(kanji),
});

export const useKanjiQuery = (identifier: string | undefined) =>
	useQuery({
		queryKey: identifier ? getKanjiShowQueryKey(identifier) : ['kanji', 'missing-identifier'],
		queryFn: () => kanjiShow(identifier as string),
		enabled: !!identifier,
		retry: false,
		select: mapKanjiDetail,
	});
