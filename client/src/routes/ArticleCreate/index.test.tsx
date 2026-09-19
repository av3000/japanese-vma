import { renderToStaticMarkup } from 'react-dom/server';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import type { UseMutationOptions } from '@tanstack/react-query';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { articleKeys } from '@/api/articles/keys';
import type { UuidCreatedResource } from '@/api/generated/model';
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

type CreateOptions = UseMutationOptions<UuidCreatedResource, unknown, StoreArticleRequest>;

const CALLBACK_CONTEXT = {} as never;

describe('ArticleCreatePage', () => {
	const invalidateQueries = vi.fn();
	let options!: CreateOptions;

	beforeEach(() => {
		navigateMock.mockReset();
		invalidateQueries.mockReset();
		vi.mocked(useQueryClient).mockReturnValue({ invalidateQueries } as never);
		vi.mocked(useMutation).mockImplementation(((mutationOptions: CreateOptions) => {
			options = mutationOptions;
			return { mutate: vi.fn(), isPending: false } as never;
		}) as never);
	});

	it('invalidates every article list variant and navigates to the new article on success', () => {
		renderToStaticMarkup(<ArticleCreatePage />);

		options.onSuccess?.(
			{ uuid: 'new-uuid' } as UuidCreatedResource,
			{} as StoreArticleRequest,
			undefined,
			CALLBACK_CONTEXT,
		);

		expect(invalidateQueries).toHaveBeenCalledWith({ queryKey: articleKeys.lists() });
		expect(invalidateQueries).not.toHaveBeenCalledWith({ queryKey: ['articles'] });
		expect(navigateMock).toHaveBeenCalledWith('/articles/new-uuid');
	});
});
