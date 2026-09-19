import { renderToStaticMarkup } from 'react-dom/server';
import { QueryClient, useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationOptions } from '@tanstack/react-query';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { articleKeys } from '@/api/articles/keys';
import type { ArticleCreatedResource } from '@/api/generated/model/articleCreatedResource';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import type { StoreArticleRequest } from '@/api/generated/model/storeArticleRequest';
import ArticleCreatePage from './index';

const navigateMock = vi.fn();

vi.mock('react-router-dom', () => ({
	useNavigate: () => navigateMock,
}));

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
	return { ...actual, useMutation: vi.fn(), useQueryClient: vi.fn() };
});

vi.mock('@/api/generated/article/article', async () => {
	const actual = await vi.importActual<typeof import('@/api/generated/article/article')>(
		'@/api/generated/article/article',
	);
	return { ...actual, articleStore: vi.fn() };
});

vi.mock('@/components/features/articles/ArticleForm', () => ({
	ArticleForm: () => null,
}));

type CreateOptions = UseMutationOptions<ArticleCreatedResource, unknown, StoreArticleRequest>;

const CALLBACK_CONTEXT = {} as never;

describe('ArticleCreatePage', () => {
	let queryClient: QueryClient;
	let options!: CreateOptions;

	const created: ArticleCreatedResource = {
		uuid: 'new-uuid',
		processing_status: {
			id: 1,
			entity_id: 'entity-uuid',
			attempt: 1,
			type: 'article_content_processing',
			status: ProcessingStatus.pending,
			metadata: {},
			created_at: '2026-09-20T10:00:00+00:00',
			updated_at: '2026-09-20T10:00:00+00:00',
		},
	};

	beforeEach(() => {
		navigateMock.mockReset();
		queryClient = new QueryClient();
		vi.mocked(useQueryClient).mockReturnValue(queryClient);
		vi.mocked(useMutation).mockImplementation(((mutationOptions: CreateOptions) => {
			options = mutationOptions;
			return { mutate: vi.fn(), isPending: false } as never;
		}) as never);
	});

	it('invalidates every article list variant and navigates to the new article on success', () => {
		const invalidate = vi.spyOn(queryClient, 'invalidateQueries');
		renderToStaticMarkup(<ArticleCreatePage />);

		options.onSuccess?.(created, {} as StoreArticleRequest, undefined, CALLBACK_CONTEXT);

		expect(invalidate).toHaveBeenCalledWith({ queryKey: articleKeys.lists() });
		expect(invalidate).not.toHaveBeenCalledWith({ queryKey: ['articles'] });
		expect(navigateMock).toHaveBeenCalledWith('/articles/new-uuid');
	});

	it('does not seed a partial detail entry for the new article', () => {
		renderToStaticMarkup(<ArticleCreatePage />);

		options.onSuccess?.(created, {} as StoreArticleRequest, undefined, CALLBACK_CONTEXT);

		// The detail page renders the full resource; the server already returns `pending` on the
		// first fetch because the row was opened in the create transaction.
		expect(queryClient.getQueryData(articleKeys.detail('new-uuid'))).toBeUndefined();
	});
});
