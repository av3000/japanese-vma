import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { articleShow, getArticleShowQueryKey } from '@/api/generated/article/article';
import type { ArticleDetailResource } from '@/api/generated/model/articleDetailResource';
import '@/shared/constants';
import { ObjectTemplateType, ObjectTemplateTypeLabel, ObjectTemplateTypeLegacyId } from '@/shared/constants/enums';
import { LikeResponse, toggleInstanceLike } from '../likes/likes';

export interface MappedArticle extends ArticleDetailResource {
	displayName: string;
	uuid: string;
	formattedDate: string;
}

export const mapArticleDetail = (data: ArticleDetailResource): MappedArticle => ({
	...data,
	uuid: data.uid,
	displayName: data.author?.name || 'Unknown Author',
	formattedDate: new Date(data.created_at).toLocaleDateString(),
});

/**
 * Single source for the article detail cache key.
 *
 * Everything that reads or reconciles a single article - the detail query, the like
 * mutation, the processing-status subscription, the edit modal and the moderation
 * status mutation - must go through here so no handwritten key can drift away from
 * the generated transport.
 */
export const getArticleDetailQueryKey = (uuid: string) => getArticleShowQueryKey(uuid);

export const useArticleQuery = (uuid: string | undefined) => {
	return useQuery({
		queryKey: getArticleDetailQueryKey(uuid as string),
		queryFn: async () => {
			return articleShow(uuid as string);
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
			toggleInstanceLike({
				objectType: ObjectTemplateTypeLabel[ObjectTemplateType.ARTICLE],
				objectTypeId: ObjectTemplateTypeLegacyId[ObjectTemplateType.ARTICLE],
				instanceId: articleId,
			}),

		onSuccess: () => {
			queryClient.invalidateQueries({ queryKey: getArticleDetailQueryKey(articleUuid) });
		},

		onError: (err) => {
			console.error('Like article failed', err);
			// TODO: Figure how to inform user - perhaps general toast message.
			// How could it be done using useReducer with  zustand
		},
	});
};
