import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { ArticleFormValues } from '@/components/features/articles/ArticleForm';
import ArticleEditPage, { buildArticleUpdatePayload } from './index';

const useArticleQueryMock = vi.fn();
const capturedProps: Array<Record<string, unknown>> = [];

let currentUser: { id: number; isAdmin: boolean } | null = { id: 5, isAdmin: false };

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');
	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
		useNavigate: () => vi.fn(),
		useParams: () => ({ article_id: 'article-uuid' }),
	};
});

vi.mock('@tanstack/react-query', async () => {
	const actual = await vi.importActual<typeof import('@tanstack/react-query')>('@tanstack/react-query');
	return {
		...actual,
		useQueryClient: () => ({ invalidateQueries: vi.fn() }),
		useMutation: () => ({ mutate: vi.fn(), isPending: false }),
	};
});

vi.mock('@/api/articles/details', async () => {
	const actual = await vi.importActual<typeof import('@/api/articles/details')>('@/api/articles/details');
	return { ...actual, useArticleQuery: (...args: unknown[]) => useArticleQueryMock(...args) };
});

vi.mock('@/hooks/useAuth', () => ({ useAuth: () => ({ user: currentUser, isAuthenticated: Boolean(currentUser) }) }));

vi.mock('@/components/features/articles/ArticleForm', () => ({
	ArticleForm: (props: Record<string, unknown>) => {
		capturedProps.push(props);
		return <div>ArticleForm</div>;
	},
}));

const article = {
	id: 321,
	uuid: 'article-uuid',
	title_jp: '新宿駅の新しい改札が完成',
	title_en: 'New ticket gates completed at Shinjuku Station',
	content_jp: '新宿駅で新しい改札が完成しました。',
	content_en: null,
	source_link: 'https://example.com/source',
	publicity: 1,
	author: { id: 5, name: 'Author' },
	hashtags: [{ id: 1, content: '#news' }],
};

const render = () => renderToStaticMarkup(<ArticleEditPage />);

describe('ArticleEdit', () => {
	beforeEach(() => {
		capturedProps.length = 0;
		currentUser = { id: 5, isAdmin: false };
		useArticleQueryMock.mockReturnValue({ data: article, isLoading: false, isError: false });
	});

	it('renders the form loader while the article loads', () => {
		useArticleQueryMock.mockReturnValue({ isLoading: true, isError: false });

		expect(render()).toContain('data-loading-family="form"');
	});

	it('renders a load failure when the article is missing', () => {
		useArticleQueryMock.mockReturnValue({ isLoading: false, isError: true });

		const html = render();

		expect(html).toContain('Article could not be loaded.');
		expect(html).toContain('href="/articles"');
	});

	it('opens the two-column form on a page, filled from the article', () => {
		const html = render();

		expect(useArticleQueryMock).toHaveBeenCalledWith('article-uuid');
		expect(html.match(/<h1[^>]*>Edit article<\/h1>/g)).toHaveLength(1);
		expect(html).toContain('href="/articles/article-uuid"');
		expect(capturedProps[0]).toMatchObject({
			initialValues: {
				title_jp: '新宿駅の新しい改札が完成',
				title_en: 'New ticket gates completed at Shinjuku Station',
				content_en: '',
				publicity: true,
				tags: ['#news'],
			},
			submitLabel: 'Save changes',
			requireEnglishTitle: true,
			requireChanges: true,
		});
		// Not `stacked`: the page has the width for the settings card beside the content.
		expect(capturedProps[0]).not.toHaveProperty('stacked');
	});

	it('refuses a viewer who is not the author, inside the page', () => {
		currentUser = { id: 42, isAdmin: false };

		const html = render();

		expect(html).toContain('You do not have permission to edit this article.');
		expect(html).toContain('Edit article');
		expect(html).not.toContain('ArticleForm');
	});

	it('refuses a guest', () => {
		currentUser = null;

		expect(render()).toContain('You do not have permission to edit this article.');
	});
});

describe('buildArticleUpdatePayload', () => {
	const values: ArticleFormValues = {
		title_jp: '題',
		title_en: '  ',
		content_jp: '本文',
		content_en: ' English ',
		source_link: 'https://example.com',
		publicity: false,
		tags: ['#a'],
	};

	it('sends only the changed fields, renaming tags and nulling emptied English fields', () => {
		expect(buildArticleUpdatePayload(values, ['title_en', 'content_en', 'tags', 'publicity'])).toEqual({
			title_en: null,
			content_en: 'English',
			hashtags: ['#a'],
			publicity: false,
		});
	});
});
