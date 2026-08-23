import { useQuery } from '@tanstack/react-query';
import { getKanjiShowQueryKey, kanjiShow } from '@/api/generated/kanji/kanji';
import type { KanjiDetailResource } from '@/api/generated/model/kanjiDetailResource';
import { getKanjiDisplayValues } from './display';

export const KANJI_DETAIL_INCLUDE = 'words,sentences,articles,viewer_catalogue_state';

const detailParams = { include: KANJI_DETAIL_INCLUDE };

export interface MappedKanji extends KanjiDetailResource {
	display: ReturnType<typeof getKanjiDisplayValues>;
	related: {
		words: NonNullable<KanjiDetailResource['words']>['items'];
		wordTotal: number;
		sentences: NonNullable<KanjiDetailResource['sentences']>['items'];
		sentenceTotal: number;
		articles: NonNullable<KanjiDetailResource['articles']>;
		articleTotal: number;
	};
}

export const mapKanjiDetail = (kanji: KanjiDetailResource): MappedKanji => ({
	...kanji,
	display: getKanjiDisplayValues(kanji),
	related: {
		words: kanji.words?.items ?? [],
		wordTotal: kanji.words?.pagination.total ?? 0,
		sentences: kanji.sentences?.items ?? [],
		sentenceTotal: kanji.sentences?.pagination.total ?? 0,
		articles: kanji.articles ?? [],
		articleTotal: kanji.articles?.length ?? 0,
	},
});

export const useKanjiQuery = (identifier: string | undefined) =>
	useQuery({
		queryKey: identifier ? getKanjiShowQueryKey(identifier, detailParams) : ['kanji', 'missing-identifier'],
		queryFn: () => kanjiShow(identifier as string, detailParams),
		enabled: !!identifier,
		retry: false,
		select: mapKanjiDetail,
	});
