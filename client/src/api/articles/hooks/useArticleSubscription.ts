import { useQueryClient } from '@tanstack/react-query';
import { LastOperationStatus } from '@/api/last-operations/last-operations';
import { useEcho } from '@/lib/echo';

// Define the shape of your Article Cache Data
// interface ArticleCacheData {
// 	data: {
// 		uuid: string;
// 		kanjis: any[]; // Or Kanji[] type
// 		processing_status: {
// 			status: string;
// 			metadata: any;
// 		} | null;
// 		[key: string]: any;
// 	};
// }

type OperationStatusPayload = {
	status: LastOperationStatus;
	metadata: Record<string, any>;
};

// TODO: Explore Orval client generator for data contracts ( types, interfaces, endpoints) generation.
export const useArticleSubscription = (articleUuid: string) => {
	const queryClient = useQueryClient();

	useEcho<OperationStatusPayload>(
		`last_operations.${articleUuid}`,
		'.OperationStatusUpdated',
		(payload) => {
			const normalizedPayload = typeof payload === 'string' ? JSON.parse(payload) : payload;
			if (import.meta.env.DEV) {
				console.log('OperationStatusUpdated', normalizedPayload);
			}

			// Optimistic Update for Detail View
			queryClient.setQueryData(['article', articleUuid], (old: any) => {
				if (!old) return old;

				return {
					...old,
					processing_status: {
						status: normalizedPayload.status,
						metadata: normalizedPayload.metadata,
					},
				};
			});

			// Optimistic Update for Infinite List View
			// We need to iterate over all cached 'articles' lists and update this specific item that is subscribed to
			queryClient.setQueryDefaults(['articles'], { staleTime: 0 }); // Mark lists as stale

			queryClient.setQueriesData({ queryKey: ['articles'] }, (oldData: any) => {
				if (!oldData) return oldData;

				return {
					...oldData,
					pages: oldData.pages.map((page: any) => ({
						...page,
						items: page.items.map((item: any) => {
							if (item.uuid === articleUuid) {
								return {
									...item,
									processing_status: {
										status: normalizedPayload.status,
										metadata: normalizedPayload.metadata,
									},
								};
							}
							return item;
						}),
					})),
				};
			});

			if (
				normalizedPayload.status === LastOperationStatus.Completed ||
				normalizedPayload.status === LastOperationStatus.Failed
			) {
				queryClient.invalidateQueries({ queryKey: ['article', articleUuid] });
			}
		},
		[articleUuid, queryClient],
		'private',
	);
};
