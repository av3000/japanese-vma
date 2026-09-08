import { describe, expect, it } from 'vitest';
import type { PostDetailResource } from '@/api/generated/model/postDetailResource';
import type { PostListItemResource } from '@/api/generated/model/postListItemResource';
import type { PostListResource } from '@/api/generated/model/postListResource';
import { getPostIndexQueryKey, getPostShowQueryKey } from '@/api/generated/post/post';
import {
	POST_LIST_DEFAULT_PER_PAGE,
	POST_TOPIC_LABELS,
	POST_TOPIC_OPTIONS,
	buildPostListSearchParams,
	getInfinitePostsQueryKey,
	getNextPostsPageParam,
	getPostDetailQueryKey,
	getPostsTotal,
	mapPostDetail,
	mapPostEngagement,
	mapPostListItem,
	parsePostListFilters,
} from './reads';

const createListItem = (overrides: Partial<PostListItemResource> = {}): PostListItemResource => ({
	id: 12,
	uuid: 'post-uuid',
	entity_type_uuid: 'a4b78a83-f180-49b5-9f8a-39500cd8fabf',
	title: 'How do I read this kanji?',
	topic: 3,
	topic_label: 'FAQ',
	locked: false,
	author: { id: 4, uuid: 'author-uuid', name: 'Hana' },
	hashtags: [{ id: 1, content: 'kanji', created_at: null, updated_at: null }],
	engagement: {
		stats: { likes_count: '3', views_count: '17', downloads_count: '0', comments_count: '2' },
	},
	created_at: '2026-09-01T10:00:00+00:00',
	updated_at: '2026-09-01T10:00:00+00:00',
	...overrides,
});

const createDetail = (overrides: Partial<PostDetailResource> = {}): PostDetailResource => ({
	...createListItem(),
	content: 'Full post body.',
	...overrides,
});

const createPage = (overrides: Partial<PostListResource['pagination']> = {}): PostListResource => ({
	items: [createListItem()],
	pagination: { page: 1, per_page: 10, total: 25, last_page: 3, has_more: true, ...overrides },
});

describe('post topic vocabulary', () => {
	it('uses the canonical write/filter labels rather than the legacy read defect', () => {
		// Legacy PostController::index/show label code 6 "Announcement" and never label 7.
		expect(POST_TOPIC_LABELS[6]).toBe('Feedback');
		expect(POST_TOPIC_LABELS[7]).toBe('Announcement');
		expect(POST_TOPIC_OPTIONS).toHaveLength(7);
	});
});

describe('parsePostListFilters', () => {
	it('defaults to newest ordering and the contract page size', () => {
		expect(parsePostListFilters(new URLSearchParams())).toEqual({
			per_page: POST_LIST_DEFAULT_PER_PAGE,
			sort: 'newest',
		});
	});

	it('reads keyword, hashtag, topic and sort from the URL', () => {
		const filters = parsePostListFilters(
			new URLSearchParams('keyword=%20kanji%20&hashtag=%23jlpt&topic=5&sort=popular'),
		);

		expect(filters).toEqual({
			per_page: POST_LIST_DEFAULT_PER_PAGE,
			sort: 'popular',
			keyword: 'kanji',
			hashtag: 'jlpt',
			topic: 5,
		});
	});

	it('drops values the contract would reject instead of forwarding a 422', () => {
		const filters = parsePostListFilters(new URLSearchParams('keyword=&topic=20&sort=oldest'));

		expect(filters).toEqual({ per_page: POST_LIST_DEFAULT_PER_PAGE, sort: 'newest' });
	});
});

describe('buildPostListSearchParams', () => {
	it('keeps an unfiltered list on a clean URL', () => {
		expect(buildPostListSearchParams({ keyword: '  ', topic: '20', sort: 'newest' }).toString()).toBe('');
	});

	it('round-trips through parsePostListFilters', () => {
		const params = buildPostListSearchParams({ keyword: 'kanji', hashtag: '#jlpt', topic: '5', sort: 'popular' });

		expect(parsePostListFilters(params)).toEqual({
			per_page: POST_LIST_DEFAULT_PER_PAGE,
			sort: 'popular',
			keyword: 'kanji',
			hashtag: 'jlpt',
			topic: 5,
		});
	});
});

describe('mapPostEngagement', () => {
	it('turns the contract string counts into numbers', () => {
		expect(mapPostEngagement(createListItem().engagement)).toEqual({
			likes: 3,
			views: 17,
			comments: 2,
			downloads: 0,
		});
	});

	it('falls back to zero for an untouched Post with null stats', () => {
		expect(mapPostEngagement({ stats: null })).toEqual({ likes: 0, views: 0, comments: 0, downloads: 0 });
		expect(mapPostEngagement(undefined)).toEqual({ likes: 0, views: 0, comments: 0, downloads: 0 });
	});
});

describe('mapPostListItem / mapPostDetail', () => {
	it('keeps the UUID identity and adds presentation fields to a list item', () => {
		const mapped = mapPostListItem(createListItem());

		expect(mapped.uuid).toBe('post-uuid');
		expect(mapped.id).toBe(12);
		expect(mapped.topic_label).toBe('FAQ');
		expect(mapped.authorName).toBe('Hana');
		expect(mapped.engagementCounts.views).toBe(17);
	});

	it('keeps detail content and survives a Post whose author name is empty', () => {
		const mapped = mapPostDetail(
			createDetail({ author: { id: 4, uuid: 'author-uuid', name: '' }, engagement: { stats: null } }),
		);

		expect(mapped.content).toBe('Full post body.');
		expect(mapped.authorName).toBe('Unknown author');
		expect(mapped.engagementCounts.likes).toBe(0);
	});
});

describe('pagination helpers', () => {
	it('advances while the contract reports more pages', () => {
		expect(getNextPostsPageParam(createPage())).toBe(2);
		expect(getNextPostsPageParam(createPage({ page: 3, has_more: false }))).toBeUndefined();
	});

	it('reads the total from the first page only', () => {
		expect(getPostsTotal([createPage(), createPage({ total: 999 })])).toBe(25);
		expect(getPostsTotal(undefined)).toBe(0);
	});
});

describe('query keys', () => {
	it('derives both read keys from the generated transport', () => {
		const filters = parsePostListFilters(new URLSearchParams('topic=5'));

		expect(getInfinitePostsQueryKey(filters)).toEqual(getPostIndexQueryKey(filters));
		expect(getPostDetailQueryKey('post-uuid')).toEqual(getPostShowQueryKey('post-uuid'));
	});

	it('keys distinct filter sets separately so pagination cannot leak across searches', () => {
		const newest = parsePostListFilters(new URLSearchParams());
		const popular = parsePostListFilters(new URLSearchParams('sort=popular'));

		expect(getInfinitePostsQueryKey(newest)).not.toEqual(getInfinitePostsQueryKey(popular));
	});
});
