import { useQuery } from '@tanstack/react-query';
import type { WordDetailResource } from '@/api/generated/model/wordDetailResource';
import { getWordShowQueryKey, wordShow } from '@/api/generated/word/word';

/**
 * The word page asks for the word's kanji (every one since #352). Its articles come from the article
 * index filtered by this word (`api/articles/related.ts`), not the five-row preview.
 */
const wordDetailParams = { include: 'kanjis' } as const;

export interface MappedWordDetail extends WordDetailResource {
	kanjis: NonNullable<WordDetailResource['kanjis']>;
}

export const mapWordDetail = (word: WordDetailResource): MappedWordDetail => ({
	...word,
	kanjis: word.kanjis ?? [],
});

export const useWordQuery = (identifier: string | undefined) =>
	useQuery({
		queryKey: identifier ? getWordShowQueryKey(identifier, wordDetailParams) : ['word', 'missing-identifier'],
		queryFn: () => wordShow(identifier as string, wordDetailParams),
		enabled: !!identifier,
		retry: false,
		select: mapWordDetail,
	});
