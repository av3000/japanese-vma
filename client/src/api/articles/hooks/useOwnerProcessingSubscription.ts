import React from 'react';
import { useQueryClient } from '@tanstack/react-query';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import { useEcho } from '@/lib/echo';
import { ownerProcessingChannel, PROCESSING_STATUS_EVENT } from '../processingChannels';
import {
	applyProcessingStatus,
	isArticleContentProcessingPayload,
	normalizeProcessingPayload,
} from '../processingStatusCache';

export { ownerProcessingChannel };

/**
 * One subscription for everything the signed-in user owns (ADR 0002 point 5, #263). The
 * backend pushes each article's status on the owner's private channel as well as the article
 * channel, and the payload carries `entity_id`, so a list with ten pending articles costs one
 * channel authorisation instead of ten.
 */
export const useOwnerProcessingSubscription = (userUuid: string) => {
	const queryClient = useQueryClient();

	useEcho<ProcessingStatusResource | string>(
		ownerProcessingChannel(userUuid),
		PROCESSING_STATUS_EVENT,
		(payload) => {
			const normalizedPayload = normalizeProcessingPayload(payload);

			if (!isArticleContentProcessingPayload(normalizedPayload)) {
				return;
			}

			applyProcessingStatus(queryClient, normalizedPayload.entity_id, normalizedPayload);
		},
		[queryClient],
		'private',
	);
};

/**
 * Mount inside a list of the user's own articles. Rendered conditionally by the caller so the
 * hook only runs with a real uuid.
 */
export const OwnerProcessingSubscription: React.FC<{ userUuid: string }> = ({ userUuid }) => {
	useOwnerProcessingSubscription(userUuid);

	return null;
};
