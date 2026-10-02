import { useQuery } from '@tanstack/react-query';
import type { WordDetailResource } from '@/api/generated/model/wordDetailResource';
import { getWordShowQueryKey, wordShow } from '@/api/generated/word/word';

const wordDetailParams = { include: 'kanjis,articles' } as const;

export interface MappedWordDetail extends WordDetailResource {
	kanjis: NonNullable<WordDetailResource['kanjis']>;
	articles: NonNullable<WordDetailResource['articles']>;
}

export const mapWordDetail = (word: WordDetailResource): MappedWordDetail => ({
	...word,
	kanjis: word.kanjis ?? [],
	articles: word.articles ?? [],
});

export const useWordQuery = (identifier: string | undefined) =>
	useQuery({
		queryKey: identifier ? getWordShowQueryKey(identifier, wordDetailParams) : ['word', 'missing-identifier'],
		queryFn: () => wordShow(identifier as string, wordDetailParams),
		enabled: !!identifier,
		retry: false,
		select: mapWordDetail,
	});
