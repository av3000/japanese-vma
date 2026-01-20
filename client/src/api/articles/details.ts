import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import '@/shared/constants';
import { ObjectTemplateType, ObjectTemplateTypeLabel, ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';
import { LikeResponse, toggleCommentLike } from '../likes/likes';
import { fetchArticle, ArticleDetails } from './articles';

export interface MappedArticle extends ArticleDetails {
	displayName: string;
	uuid: string;
	formattedDate: string;
}

export const useArticleQuery = (uuid: string | undefined) => {
	return useQuery({
		queryKey: ['article', uuid],
		queryFn: () => fetchArticle(uuid as string),
		enabled: !!uuid,
		retry: false,
		select: (data): MappedArticle => ({
			...data,
			uuid: data.uid,
			displayName: data.author?.name || 'Unknown Author',
			formattedDate: new Date(data.created_at).toLocaleDateString(),
		}),
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
			// refetch the article detail (and optionally lists)
			queryClient.invalidateQueries({ queryKey: ['article', articleUuid] });
			// optionally:
			// queryClient.invalidateQueries({ queryKey: ['articles'] });
		},

		onError: (err) => {
			console.error('Like article failed', err);
		},
	});
};
