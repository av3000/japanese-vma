import type { EngagementStatsResource } from '@/api/generated/model/engagementStatsResource';
import type { PostDetailResource } from '@/api/generated/model/postDetailResource';
import { useToggleLikeMutation, type LikeCacheBinding } from '@/api/likes/likes';
import { ObjectTemplateType } from '@/shared/constants/enums';
import { getPostDetailQueryKey } from './reads';

/** A Post nobody has engaged with has `stats: null`; a like is the first count it gets. */
const NO_STATS: EngagementStatsResource = {
	likes_count: '0',
	views_count: '0',
	downloads_count: '0',
	comments_count: '0',
};

/**
 * Post detail carries the viewer's own like state (#529), so the toggle flips the cached record
 * optimistically and settles on the served count, as Article and Catalogue detail do.
 */
const buildPostLikeBinding = (detailIdentifier: string): LikeCacheBinding<PostDetailResource> => ({
	queryKey: getPostDetailQueryKey(detailIdentifier),

	read: (post) => ({
		is_liked: post.engagement.is_liked_by_viewer,
		likes_count: Number(post.engagement.stats?.likes_count ?? 0),
	}),

	write: (post, _instanceId, next) => ({
		...post,
		engagement: {
			...post.engagement,
			is_liked_by_viewer: next.is_liked,
			stats: {
				...(post.engagement.stats ?? NO_STATS),
				// `EngagementStatsResource` types every count as a string on the wire.
				likes_count: String(next.likes_count),
			},
		},
	}),
});

/**
 * Toggles the like on the post the detail route is showing. The mutation variable is the post's
 * loaded numeric `id`; the identifier only addresses the cache.
 */
export const useLikePostMutation = (detailIdentifier: string) =>
	useToggleLikeMutation({
		template: ObjectTemplateType.POST,
		binding: buildPostLikeBinding(detailIdentifier),
	});
