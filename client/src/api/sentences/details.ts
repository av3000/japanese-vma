import { useQuery } from '@tanstack/react-query';
import {
	getSentenceShowQueryKey,
	sentenceShow,
} from '@/api/generated/sentence/sentence';
import type { SentenceResource } from '@/api/generated/model/sentenceResource';

export type SentenceDetailResponse = SentenceResource;

export type MappedSentenceDetail = Omit<SentenceDetailResponse, 'words'> & {
	kanjis: SentenceDetailResponse['kanjis'];
};

export const mapSentenceDetail = (sentence: SentenceDetailResponse): MappedSentenceDetail => {
	return {
		id: sentence.id,
		uuid: sentence.uuid,
		user_id: sentence.user_id,
		tatoeba_entry: sentence.tatoeba_entry,
		content: sentence.content,
		kanjis: sentence.kanjis ?? [],
	};
};

export const useSentenceQuery = (identifier: string | undefined) => {
	return useQuery({
		queryKey: identifier ? getSentenceShowQueryKey(identifier) : ['sentence', 'missing-identifier'],
		queryFn: () => sentenceShow(identifier as string),
		enabled: !!identifier,
		retry: false,
		select: mapSentenceDetail,
	});
};
