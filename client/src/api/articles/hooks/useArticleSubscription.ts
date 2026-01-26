import { useEffect } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { useWebSocket } from '@/providers/contexts/socket-provider';

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

export const lastOperationStatuses = ['pending', 'processing', 'completed', 'failed'] as const;

export type LastOperationStatus = (typeof lastOperationStatuses)[number];

// TODO: Explore Orval client generator for data contracts ( types, interfaces, endpoints) generation.
export const useArticleSubscription = (articleUuid: string | undefined) => {
	const { echo } = useWebSocket();
	const queryClient = useQueryClient();

	useEffect(() => {
		if (!echo || !articleUuid) return;

		const channelName = `last_operations.${articleUuid}`;
		console.log(`🔌 Subscribing to: ${channelName}`);

		const channel = echo.private(channelName);

		channel.listen('.OperationStatusUpdated', (event: any) => {
			const payload = typeof event === 'string' ? JSON.parse(event) : event;
			if (import.meta.env.DEV) {
				console.log('OperationStatusUpdated', payload);
			}

			// Optimistic Update for Detail View
			queryClient.setQueryData(['article', articleUuid], (old: any) => {
				if (!old) return old;

				return {
					...old,
					processing_status: {
						status: payload.status,
						metadata: payload.metadata,
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
										status: payload.status,
										metadata: payload.metadata,
									},
								};
							}
							return item;
						}),
					})),
				};
			});
		});

		return () => {
			channel.stopListening('.OperationStatusUpdated');
		};
	}, [echo, articleUuid, queryClient]);
};
