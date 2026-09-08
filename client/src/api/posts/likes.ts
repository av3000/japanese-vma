import type { PostDetailResource } from '@/api/generated/model/postDetailResource';
import { useToggleLikeMutation, type LikeCacheBinding } from '@/api/likes/likes';
import { ObjectTemplateType } from '@/shared/constants/enums';
import { getPostDetailQueryKey } from './reads';

/**
 * Post detail exposes aggregate engagement only: `EngagementStatsSummaryResource` has no
 * `is_liked_by_viewer`, unlike the Article, Catalogue and Comment contracts.
 *
 * So `read` reports nothing to flip and no optimistic write happens - the cached count moves only
 * on the authoritative response, and the viewer's own state comes from that response rather than
 * from the cache. Giving Post the same optimistic treatment as the others needs the read contract
 * to carry the viewer flag first.
 */
const buildPostLikeBinding = (detailIdentifier: string): LikeCacheBinding<PostDetailResource> => ({
	queryKey: getPostDetailQueryKey(detailIdentifier),

	read: () => undefined,

	write: (post, _instanceId, next) =>
		post.engagement.stats
			? {
					...post,
					engagement: {
						...post.engagement,
						stats: {
							...post.engagement.stats,
							// `EngagementStatsResource` types every count as a string on the wire.
							likes_count: String(next.likes_count),
						},
					},
				}
			: post,
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
