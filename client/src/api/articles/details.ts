import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import '@/shared/constants';
import { articleShow } from '@/api/generated/article/article';
import type { ArticleDetailResourceArticle } from '@/api/generated/model/articleDetailResourceArticle';
import type { LastOperationEvent, LastOperationStatus } from '@/api/last-operations/last-operations';
import { ObjectTemplateType, ObjectTemplateTypeLabel, ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';
import { LikeResponse, toggleCommentLike } from '../likes/likes';

export interface MappedArticle extends Omit<ArticleDetailResourceArticle, 'processing_status'> {
	displayName: string;
	uuid: string;
	formattedDate: string;
	processing_status: LastOperationEvent | null;
}

const mapProcessingStatus = (
	processingStatus: ArticleDetailResourceArticle['processing_status'],
): LastOperationEvent | null => {
	if (!processingStatus?.status) return null;

	return {
		id: processingStatus.id ?? 0,
		type: processingStatus.type ?? '',
		status: processingStatus.status as LastOperationStatus,
		metadata: processingStatus.metadata ?? {},
		created_at: processingStatus.created_at,
		updated_at: processingStatus.updated_at,
	};
};

export const mapArticleDetail = (data: ArticleDetailResourceArticle): MappedArticle => ({
	...data,
	uuid: data.uid,
	displayName: data.author?.name || 'Unknown Author',
	formattedDate: new Date(data.created_at).toLocaleDateString(),
	processing_status: mapProcessingStatus(data.processing_status),
});

export const useArticleQuery = (uuid: string | undefined) => {
	return useQuery({
		queryKey: ['article', uuid],
		queryFn: async () => {
			const detail = await articleShow(uuid as string);
			return detail.data.article;
		},
		enabled: !!uuid,
		retry: false,
		select: mapArticleDetail,
	});
};

export const useLikeArticleMutation = (articleUuid: string) => {
	const queryClient = useQueryClient();

	return useMutation<LikeResponse, unknown, number>({
		mutationFn: (articleId: number) =>
			toggleCommentLike({
				objectType: ObjectTemplateTypeLabel[ObjectTemplateType.ARTICLE],
				objectTypeId: ObjectTemplateTypeLegacyId[ObjectTemplateType.ARTICLE],
				instanceId: articleId,
			}),

		onSuccess: () => {
			queryClient.invalidateQueries({ queryKey: ['article', articleUuid] });
		},

		onError: (err) => {
			console.error('Like article failed', err);
			// TODO: Figure how to inform user - perhaps general toast message.
		},
	});
};
