import { QueryClient, useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationOptions } from '@tanstack/react-query';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { likeLikeInstance } from '@/api/generated/like/like';
import { ArticleDetailResource } from '@/api/generated/model/articleDetailResource';
import { LastOperationStatus } from '@/api/generated/model/lastOperationStatus';
import { LikeTargetType } from '@/api/generated/model/likeTargetType';
import type { LikeToggleContext } from '@/api/likes/likes';
import { getArticleDetailQueryKey, mapArticleDetail, useLikeArticleMutation } from './details';

/**
 * react-query 5.90 hands every mutation callback a trailing `MutationFunctionContext` that the
 * Like seam does not read; these tests only exercise the arguments it does.
 */
const CALLBACK_CONTEXT = {} as never;

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
	return { ...actual, useMutation: vi.fn(), useQueryClient: vi.fn() };
});

vi.mock('@/api/generated/like/like', () => ({
	likeLikeInstance: vi.fn(),
}));

type ArticleLikeOptions = UseMutationOptions<
	{ is_liked: boolean; likes_count: number },
	unknown,
	number,
	LikeToggleContext<ArticleDetailResource>
>;

/** The seam always sets these four; narrowing here keeps the assertions free of `!`. */
type LikeCallbacks = Required<Pick<ArticleLikeOptions, 'mutationFn' | 'onMutate' | 'onSuccess' | 'onError'>>;

/**
 * Drives the real like seam against a real cache: only the React plumbing is mocked, so the
 * assertions below are about what actually lands in the query cache.
 */
const useArticleLikeHarness = (cached?: ArticleDetailResource) => {
	const queryClient = new QueryClient();

	if (cached) {
		queryClient.setQueryData(getArticleDetailQueryKey(cached.uid), cached);
	}

	let options!: LikeCallbacks;
	vi.mocked(useQueryClient).mockReturnValue(queryClient);
	vi.mocked(useMutation).mockImplementation((mutationOptions: any) => {
		options = mutationOptions;
		return { mutate: vi.fn(), isPending: false, variables: undefined } as never;
	});

	useLikeArticleMutation('article-uuid');

	return { queryClient, options };
};

const createArticle = (overrides: Partial<ArticleDetailResource> = {}): ArticleDetailResource => ({
	id: 123,
	uid: 'article-uuid',
	entity_type_uid: 'entity-type-uuid',
	title_jp: '日本語の記事',
	title_en: 'Japanese article',
	content_jp: 'これはテスト記事です。',
	content_en: 'This is a test article.',
	source_link: 'https://example.com/article',
	publicity: 1,
	status: 3,
	jlpt_levels: {
		n1: 0,
		n2: 1,
		n3: 2,
		n4: 3,
		n5: 4,
		uncommon: 5,
	},
	author: {
		id: 7,
		uuid: 'author-uuid',
		name: 'Aki',
	},
	hashtags: [],
	created_at: '2026-04-01T12:00:00.000Z',
	updated_at: '2026-04-02T12:00:00.000Z',
	engagement: {
		is_liked_by_viewer: true,
		likes_count: 2,
		views_count: 5,
		downloads_count: 1,
	},
	kanjis: [],
	words: [],
	processing_status: null,
	...overrides,
});

describe('mapArticleDetail', () => {
	it('maps generated detail article data to the route-facing article shape', () => {
		const article = mapArticleDetail(createArticle());

		expect(article.uuid).toBe('article-uuid');
		expect(article.displayName).toBe('Aki');
		expect(article.formattedDate).toBe(new Date('2026-04-01T12:00:00.000Z').toLocaleDateString());
		expect(article.engagement?.likes_count).toBe(2);
		expect(article.words).toEqual([]);
	});

	it('falls back to an unknown author display name when the generated author is absent', () => {
		const article = mapArticleDetail(createArticle({ author: undefined as any }));

		expect(article.displayName).toBe('Unknown Author');
	});

	it('keeps generated processing status metadata and enum status on mapped article details', () => {
		const article = mapArticleDetail(
			createArticle({
				processing_status: {
					id: 10,
					type: 'kanji_extraction',
					status: LastOperationStatus.completed,
					metadata: {
						message: 'Attached 76 kanjis.',
						kanji_count: 76,
					},
					created_at: '2026-04-01T12:00:00.000Z',
					updated_at: '2026-04-01T12:01:00.000Z',
				},
			}),
		);

		expect(article.processing_status?.status).toBe(LastOperationStatus.completed);
		expect(article.processing_status?.metadata?.kanji_count).toBe(76);
	});
});

describe('useLikeArticleMutation', () => {
	beforeEach(() => {
		vi.clearAllMocks();
	});

	it('sends the generated like request for the loaded numeric article id', async () => {
		vi.mocked(likeLikeInstance).mockResolvedValue({ is_liked: false, likes_count: 1 });
		const { options } = useArticleLikeHarness(createArticle());

		await options.mutationFn(123, CALLBACK_CONTEXT);

		expect(likeLikeInstance).toHaveBeenCalledWith({
			template_id: LikeTargetType.NUMBER_1,
			real_object_id: 123,
		});
	});

	it('refuses the article uuid in place of the numeric id', async () => {
		const { options } = useArticleLikeHarness(createArticle());

		await expect(options.mutationFn('article-uuid' as unknown as number, CALLBACK_CONTEXT)).rejects.toThrow();
		expect(likeLikeInstance).not.toHaveBeenCalled();
	});

	it('patches only the engagement block of the cached article when unliking', async () => {
		const article = createArticle();
		const { queryClient, options } = useArticleLikeHarness(article);

		await options.onMutate(123, CALLBACK_CONTEXT);

		const patched = queryClient.getQueryData<ArticleDetailResource>(getArticleDetailQueryKey('article-uuid'));
		expect(patched?.engagement).toEqual({
			is_liked_by_viewer: false,
			likes_count: 1,
			views_count: 5,
			downloads_count: 1,
		});
		expect(patched?.title_en).toBe(article.title_en);
	});

	it('patches the cached article when liking a previously unliked article', async () => {
		const { queryClient, options } = useArticleLikeHarness(
			createArticle({
				engagement: { is_liked_by_viewer: false, likes_count: 2, views_count: 5, downloads_count: 1 },
			}),
		);

		await options.onMutate(123, CALLBACK_CONTEXT);

		expect(
			queryClient.getQueryData<ArticleDetailResource>(getArticleDetailQueryKey('article-uuid'))?.engagement,
		).toMatchObject({ is_liked_by_viewer: true, likes_count: 3 });
	});

	it('rolls the cached article back to its exact pre-click state when the toggle is rejected', async () => {
		const article = createArticle();
		const { queryClient, options } = useArticleLikeHarness(article);

		const context = await options.onMutate(123, CALLBACK_CONTEXT);
		options.onError({ response: { status: 401 } } as never, 123, context, CALLBACK_CONTEXT);

		expect(queryClient.getQueryData<ArticleDetailResource>(getArticleDetailQueryKey('article-uuid'))).toEqual(
			article,
		);
	});

	it('settles the cached article on the server response rather than the optimistic guess', async () => {
		const { queryClient, options } = useArticleLikeHarness(createArticle());

		const context = await options.onMutate(123, CALLBACK_CONTEXT);
		options.onSuccess({ is_liked: true, likes_count: 8 }, 123, context, CALLBACK_CONTEXT);

		expect(
			queryClient.getQueryData<ArticleDetailResource>(getArticleDetailQueryKey('article-uuid'))?.engagement,
		).toMatchObject({ is_liked_by_viewer: true, likes_count: 8 });
	});
});
