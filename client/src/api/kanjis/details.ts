import { useQuery } from '@tanstack/react-query';
import { getKanjiShowQueryKey, kanjiShow } from '@/api/generated/kanji/kanji';
import type { KanjiDetailResource } from '@/api/generated/model/kanjiDetailResource';

/**
 * The kanji page asks only for the viewer's save state. Its words, sentences and articles come
 * from the indexes filtered by this kanji (`api/kanjis/related.ts`, #352), not from the capped
 * five-row previews the detail endpoint can include.
 */
export const KANJI_DETAIL_INCLUDE = 'viewer_catalogue_state';

const detailParams = { include: KANJI_DETAIL_INCLUDE };

export type MappedKanji = KanjiDetailResource;

export const useKanjiQuery = (identifier: string | undefined) =>
	useQuery({
		queryKey: identifier ? getKanjiShowQueryKey(identifier, detailParams) : ['kanji', 'missing-identifier'],
		queryFn: () => kanjiShow(identifier as string, detailParams),
		enabled: !!identifier,
		retry: false,
	});
