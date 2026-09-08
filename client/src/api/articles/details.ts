import { useQuery } from '@tanstack/react-query';
import { articleShow, getArticleShowQueryKey } from '@/api/generated/article/article';
import type { ArticleDetailResource } from '@/api/generated/model/articleDetailResource';
import { useToggleLikeMutation, type LikeCacheBinding } from '@/api/likes/likes';
import '@/shared/constants';
import { ObjectTemplateType } from '@/shared/constants/enums';

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

/**
 * Article detail carries the viewer's own like state, so a toggle can be reflected in place
 * instead of refetching the whole article for two numbers.
 */
const buildArticleLikeBinding = (articleUuid: string): LikeCacheBinding<ArticleDetailResource> => ({
	queryKey: getArticleDetailQueryKey(articleUuid),

	read: (article) => ({
		is_liked: article.engagement.is_liked_by_viewer,
		likes_count: article.engagement.likes_count,
	}),

	write: (article, _instanceId, next) => ({
		...article,
		engagement: {
			...article.engagement,
			is_liked_by_viewer: next.is_liked,
			likes_count: next.likes_count,
		},
	}),
});

/**
 * Toggles the like on the article the detail route is showing. The mutation variable is the
 * article's loaded numeric `id`; the uuid only addresses the cache.
 */
export const useLikeArticleMutation = (articleUuid: string) =>
	useToggleLikeMutation({
		template: ObjectTemplateType.ARTICLE,
		binding: buildArticleLikeBinding(articleUuid),
	});
