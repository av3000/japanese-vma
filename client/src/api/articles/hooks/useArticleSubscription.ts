import { useQueryClient } from '@tanstack/react-query';
import { LastOperationStatus } from '@/api/generated/model/lastOperationStatus';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
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

type OperationStatusPayload = ProcessingStatusResource;

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
						...(old.processing_status ?? {}),
						...normalizedPayload,
					},
				};
			});

			queryClient.setQueriesData({ queryKey: ['articles'] }, (oldData: any) => {
				if (!oldData) return oldData;
				if (!Array.isArray(oldData.pages)) return oldData;

				return {
					...oldData,
					pages: oldData.pages.map((page: any) => ({
						...page,
						data: page.data
							? {
									...page.data,
									items: page.data.items.map((item: any) => {
										if (item.uuid !== articleUuid) {
											return item;
										}

										return {
											...item,
											processing_status: {
												...(item.processing_status ?? {}),
												...normalizedPayload,
											},
										};
									}),
								}
							: page.items
								? {
										...page,
										items: page.items.map((item: any) => {
											if (item.uuid !== articleUuid) {
												return item;
											}

											return {
												...item,
												processing_status: {
													...(item.processing_status ?? {}),
													...normalizedPayload,
												},
											};
										}),
									}
								: page.data,
					})),
				};
			});

			if (
				normalizedPayload.status === LastOperationStatus.completed ||
				normalizedPayload.status === LastOperationStatus.failed
			) {
				queryClient.invalidateQueries({ queryKey: ['article', articleUuid] });
			}
		},
		[articleUuid, queryClient],
		'private',
	);
};
