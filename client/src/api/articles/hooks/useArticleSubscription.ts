import { useQueryClient } from '@tanstack/react-query';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import { useEcho } from '@/lib/echo';
import { applyProcessingStatus } from '../processingStatusCache';

const normalizePayload = (payload: ProcessingStatusResource | string): ProcessingStatusResource =>
	typeof payload === 'string' ? (JSON.parse(payload) as ProcessingStatusResource) : payload;

/**
 * Fast path for processing status: listens on the article's private channel and writes each
 * event into the same cache entries the polling fallback refreshes.
 */
export const useArticleSubscription = (articleUuid: string) => {
	const queryClient = useQueryClient();

	useEcho<ProcessingStatusResource | string>(
		`last_operations.${articleUuid}`,
		'.OperationStatusUpdated',
		(payload) => {
			const normalizedPayload = normalizePayload(payload);

			if (import.meta.env.DEV) {
				console.log('OperationStatusUpdated', normalizedPayload);
			}

			applyProcessingStatus(queryClient, articleUuid, normalizedPayload);
		},
		[articleUuid, queryClient],
		'private',
	);
};
