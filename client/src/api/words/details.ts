import { useQuery } from '@tanstack/react-query';
import { getWordShowQueryKey, wordShow } from '@/api/generated/word/word';
import type { WordShow200 } from '@/api/generated/model/wordShow200';

const wordDetailParams = { include: 'kanjis,articles' } as const;

export interface MappedWordDetail extends WordShow200 {
	kanjis: NonNullable<WordShow200['kanjis']>;
	articles: NonNullable<WordShow200['articles']>;
}

export const mapWordDetail = (word: WordShow200): MappedWordDetail => ({
	...word,
	kanjis: word.kanjis ?? [],
	articles: word.articles ?? [],
});

export const useWordQuery = (identifier: string | undefined) =>
	useQuery({
		queryKey: identifier
			? getWordShowQueryKey(identifier, wordDetailParams)
			: ['word', 'missing-identifier'],
		queryFn: () => wordShow(identifier as string, wordDetailParams),
		enabled: !!identifier,
		retry: false,
		select: mapWordDetail,
	});
