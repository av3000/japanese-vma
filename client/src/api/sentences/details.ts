import { useQuery } from '@tanstack/react-query';
import {
	getSentenceShowQueryKey,
	sentenceShow,
} from '@/api/generated/sentence/sentence';
import type { SentenceShow200 } from '@/api/generated/model/sentenceShow200';

export type SentenceDetailResponse = SentenceShow200;

export type MappedSentenceDetail = SentenceDetailResponse;

export const mapSentenceDetail = (
	sentence: SentenceDetailResponse,
): MappedSentenceDetail => ({
	...sentence,
	kanjis: sentence.kanjis ?? [],
	words: sentence.words ?? [],
});

export const useSentenceQuery = (identifier: string | undefined) => {
	return useQuery({
		queryKey: identifier ? getSentenceShowQueryKey(identifier) : ['sentence', 'missing-identifier'],
		queryFn: () => sentenceShow(identifier as string),
		enabled: !!identifier,
		retry: false,
		select: mapSentenceDetail,
	});
};
