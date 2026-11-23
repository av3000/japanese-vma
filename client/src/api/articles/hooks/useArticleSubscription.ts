import { useQueryClient } from '@tanstack/react-query';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import { useEcho } from '@/lib/echo';
import { articleProcessingChannel, PROCESSING_STATUS_EVENT } from '../processingChannels';
import {
	applyProcessingStatus,
	isArticleContentProcessingPayload,
	normalizeProcessingPayload,
} from '../processingStatusCache';

/**
 * Fast path for one article's processing status: listens on the article's private channel and
 * writes each event into the same cache entries the polling fallback refreshes. Used by the
 * detail page only; lists never open a channel per article (#263).
 */
export const useArticleSubscription = (articleUuid: string) => {
	const queryClient = useQueryClient();

	useEcho<ProcessingStatusResource | string>(
		articleProcessingChannel(articleUuid),
		PROCESSING_STATUS_EVENT,
		(payload) => {
			const normalizedPayload = normalizeProcessingPayload(payload);

			if (import.meta.env.DEV) {
				console.log('ProcessingStatusUpdated', normalizedPayload);
			}

			if (!isArticleContentProcessingPayload(normalizedPayload)) {
				return;
			}

			applyProcessingStatus(queryClient, articleUuid, normalizedPayload);
		},
		[articleUuid, queryClient],
		'private',
	);
};
