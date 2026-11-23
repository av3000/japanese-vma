import { keepPreviousData, useInfiniteQuery, useQuery, useQueryClient } from '@tanstack/react-query';
import type { InfiniteData } from '@tanstack/react-query';
import type { EngagementStatsSummaryResource } from '@/api/generated/model/engagementStatsSummaryResource';
import type { PostDetailResource } from '@/api/generated/model/postDetailResource';
import type { PostIndexParams } from '@/api/generated/model/postIndexParams';
import type { PostListItemResource } from '@/api/generated/model/postListItemResource';
import type { PostListResource } from '@/api/generated/model/postListResource';
import { PostSort } from '@/api/generated/model/postSort';
import { PostTopic } from '@/api/generated/model/postTopic';
import { getPostIndexQueryKey, getPostShowQueryKey, postIndex, postShow } from '@/api/generated/post/post';
import type { PostIndexQueryError } from '@/api/generated/post/post';

export const POST_LIST_DEFAULT_PER_PAGE = 10;

export const POST_ROUTES = {
	list: '/community',
	detail: (identifier: string | number) => `/community/${identifier}`,
	create: '/newpost',
	edit: (postId: string | number) => `/community/edit/${postId}`,
} as const;

/**
 * Canonical topic vocabulary, matching the v1 `PostTopic` contract.
 *
 * The legacy read paths label code 6 as "Announcement" and never label code 7. That defect is
 * deliberately not reproduced here; the backend already sends the canonical `topic_label` on every
 * resource, so these labels only drive the filter control.
 */
export const POST_TOPIC_LABELS = {
	[PostTopic.NUMBER_1]: 'Content-related',
	[PostTopic.NUMBER_2]: 'Off-topic',
	[PostTopic.NUMBER_3]: 'FAQ',
	[PostTopic.NUMBER_4]: 'Technical',
	[PostTopic.NUMBER_5]: 'Bug',
	[PostTopic.NUMBER_6]: 'Feedback',
	[PostTopic.NUMBER_7]: 'Announcement',
} satisfies Record<PostTopic, string>;

export const POST_TOPIC_OPTIONS = Object.entries(POST_TOPIC_LABELS).map(([value, label]) => ({
	value: Number(value) as PostTopic,
	label,
}));

export const isPostTopic = (value: number): value is PostTopic => {
	return Object.prototype.hasOwnProperty.call(POST_TOPIC_LABELS, value);
};

export const isPostSort = (value: string): value is PostSort => {
	return Object.prototype.hasOwnProperty.call(PostSort, value);
};

export type PostListFilters = Omit<PostIndexParams, 'page'>;

/**
 * The list route owns its filters through the URL, so this is the one place that reads them.
 * Unknown or malformed values fall back to the contract defaults instead of reaching the API and
 * coming back as a 422.
 */
export const parsePostListFilters = (searchParams: URLSearchParams): PostListFilters => {
	const keyword = searchParams.get('keyword')?.trim();
	const hashtag = searchParams.get('hashtag')?.trim().replace(/^#/, '');
	const topic = Number(searchParams.get('topic'));
	const sort = searchParams.get('sort') ?? '';

	return {
		per_page: POST_LIST_DEFAULT_PER_PAGE,
		sort: isPostSort(sort) ? sort : PostSort.newest,
		...(keyword ? { keyword } : {}),
		...(hashtag ? { hashtag } : {}),
		...(isPostTopic(topic) ? { topic } : {}),
	};
};

export type PostListFilterInput = {
	keyword?: string;
	hashtag?: string;
	topic?: string;
	sort?: string;
};

/**
 * Inverse of {@link parsePostListFilters}: only meaningful values reach the URL, so an unfiltered
 * list keeps a clean address and every filter combination has exactly one representation.
 */
export const buildPostListSearchParams = ({ keyword, hashtag, topic, sort }: PostListFilterInput) => {
	const params = new URLSearchParams();
	const trimmedKeyword = keyword?.trim();
	const trimmedHashtag = hashtag?.trim().replace(/^#/, '');
	const topicCode = Number(topic);

	if (trimmedKeyword) {
		params.set('keyword', trimmedKeyword);
	}

	if (trimmedHashtag) {
		params.set('hashtag', trimmedHashtag);
	}

	if (isPostTopic(topicCode)) {
		params.set('topic', String(topicCode));
	}

	if (sort && isPostSort(sort) && sort !== PostSort.newest) {
		params.set('sort', sort);
	}

	return params;
};

export interface PostEngagementCounts {
	likes: number;
	views: number;
	comments: number;
	downloads: number;
}

/**
 * `EngagementStatsResource` types every count as a string and the whole `stats` object is nullable
 * for a Post nobody has touched yet. Presentation wants numbers, so the coercion happens once here.
 */
const toCount = (value: string | undefined): number => {
	const parsed = Number(value);

	return Number.isFinite(parsed) ? parsed : 0;
};

export const mapPostEngagement = (engagement: EngagementStatsSummaryResource | undefined): PostEngagementCounts => ({
	likes: toCount(engagement?.stats?.likes_count),
	views: toCount(engagement?.stats?.views_count),
	comments: toCount(engagement?.stats?.comments_count),
	downloads: toCount(engagement?.stats?.downloads_count),
});

interface MappedPostFields {
	engagementCounts: PostEngagementCounts;
	authorName: string;
	formattedDate: string;
}

export type MappedPostListItem = PostListItemResource & MappedPostFields;
export type MappedPostDetail = PostDetailResource & MappedPostFields;

const mapSharedPostFields = (post: PostListItemResource | PostDetailResource): MappedPostFields => ({
	engagementCounts: mapPostEngagement(post.engagement),
	authorName: post.author?.name || 'Unknown author',
	formattedDate: new Date(post.created_at).toLocaleDateString(),
});

export const mapPostListItem = (post: PostListItemResource): MappedPostListItem => ({
	...post,
	...mapSharedPostFields(post),
});

export const mapPostDetail = (post: PostDetailResource): MappedPostDetail => ({
	...post,
	...mapSharedPostFields(post),
});

export const getInfinitePostsQueryKey = (filters: PostListFilters = {}) => getPostIndexQueryKey(filters);

export const getNextPostsPageParam = (lastPage: PostListResource) =>
	lastPage.pagination.has_more ? lastPage.pagination.page + 1 : undefined;

export const getPostsTotal = (pages: PostListResource[] | undefined) => pages?.[0]?.pagination.total ?? 0;

type UseInfinitePostsOptions = {
	enabled?: boolean;
	filters?: PostListFilters;
};

export const useInfinitePosts = ({ enabled = true, filters = {} }: UseInfinitePostsOptions = {}) => {
	const query = useInfiniteQuery<
		PostListResource,
		PostIndexQueryError,
		InfiniteData<PostListResource>,
		ReturnType<typeof getInfinitePostsQueryKey>,
		number
	>({
		queryKey: getInfinitePostsQueryKey(filters),
		queryFn: ({ pageParam, signal }) => postIndex({ ...filters, page: pageParam }, undefined, signal),
		initialPageParam: 1,
		getNextPageParam: getNextPostsPageParam,
		enabled,
		// Filter changes swap the query key. Without this the list would blank out and fall back to
		// the empty-first loader that this slice exists to remove.
		placeholderData: keepPreviousData,
	});

	const pages = query.data?.pages;
	const posts = pages?.flatMap((page) => page.items.map(mapPostListItem)) ?? [];

	return {
		...query,
		posts,
		total: getPostsTotal(pages),
	};
};

/**
 * Single source for the Post detail cache key, so the detail query, the transitional numeric
 * redirect, and later write/like slices cannot drift onto different keys for the same Post.
 */
export const getPostDetailQueryKey = (identifier: string) => getPostShowQueryKey(identifier);

export const usePostQuery = (identifier: string | undefined) => {
	const queryClient = useQueryClient();

	return useQuery({
		queryKey: getPostDetailQueryKey(identifier as string),
		queryFn: async ({ signal }) => {
			const post = await postShow(identifier as string, undefined, signal);

			// A transitional numeric URL resolves to the same Post. Seeding the canonical key means the
			// redirect to the UUID reads from cache instead of issuing a second GET, which would also
			// record a second view for an authenticated reader.
			if (post.uuid !== identifier) {
				queryClient.setQueryData(getPostDetailQueryKey(post.uuid), post);
			}

			return post;
		},
		enabled: !!identifier,
		retry: false,
		select: mapPostDetail,
	});
};
