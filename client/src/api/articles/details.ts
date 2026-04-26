import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { articleShow } from '@/api/generated/article/article';
import type { ArticleDetailResourceArticle } from '@/api/generated/model/articleDetailResourceArticle';
import '@/shared/constants';
import { ObjectTemplateType, ObjectTemplateTypeLabel, ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';
import { LikeResponse, toggleCommentLike } from '../likes/likes';

export interface MappedArticle extends ArticleDetailResourceArticle {
	displayName: string;
	uuid: string;
	formattedDate: string;
}

export const mapArticleDetail = (data: ArticleDetailResourceArticle): MappedArticle => ({
	...data,
	uuid: data.uid,
	displayName: data.author?.name || 'Unknown Author',
	formattedDate: new Date(data.created_at).toLocaleDateString(),
});

export const useArticleQuery = (uuid: string | undefined) => {
	return useQuery({
		queryKey: ['article', uuid],
		queryFn: async () => {
			const detail = await articleShow(uuid as string);
			return detail.article;
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
			// How could it be done using useReducer with  zustand
		},
	});
};
