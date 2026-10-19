import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { getArticleIndexMockHandler } from '@/api/generated/article/article.msw';
import type { ArticleListResource } from '@/api/generated/model';
import { latestArticles, libraryArticles, makeArticle } from '@/components/features/Homepage/fixtures';
import { articleFacets } from '@/components/features/articles/fixtures';
import { currentLocation } from '@/storybook/decorators';
import { users } from '@/storybook/fixtures/users';
import { createRequestLog, noAppliedArticleCriteria, pageOf } from '../pageFixtures';
import ArticlesList from './';
import ArticlesListSkeleton from './ArticlesListSkeleton/ArticlesListSkeleton';
import { DEFAULT_PER_PAGE } from './articleListSearchParams';

/*
 * `/articles` on its real route component and hooks: the URL owns every filter, and the index
 * request goes through the generated MSW handler. The list pages by Load more (an infinite
 * query), so there is no `page` in the URL; the paged story asks for the second page.
 */

const TOTAL = 30;

/** The hostile rows first, then plain numbered ones, so a page always has unique ids and uuids. */
const rowAt = (position: number) => {
	const hostile = [
		...latestArticles,
		libraryArticles.longest,
		libraryArticles.zeroEverything,
		libraryArticles.processing,
	][position - 1];

	return makeArticle({
		...hostile,
		id: position,
		uuid: `article-${position}`,
		title_jp: hostile?.title_jp ?? `記事 ${position}`,
		title_en: hostile ? hostile.title_en : `Article ${position}`,
	});
};

const requests = createRequestLog();

const answer = (request: Request, total: number): ArticleListResource => ({
	...pageOf(request, total, rowAt, DEFAULT_PER_PAGE),
	facets: articleFacets,
	applied: noAppliedArticleCriteria,
});

/** Answers every index request with `total` rows, and records its query string. */
const articlesIndex = (total = TOTAL) =>
	getArticleIndexMockHandler(({ request }) => {
		requests.record(request);

		return answer(request, total);
	});

const meta = {
	title: 'Pages/ArticlesList/Page',
	component: ArticlesList,
	parameters: {
		layout: 'fullscreen',
		router: { path: '/articles', initialEntries: ['/articles'] },
	},
	beforeEach: ({ msw }) => {
		requests.clear();
		msw.use(articlesIndex());
	},
} satisfies Meta<typeof ArticlesList>;

export default meta;

type Story = StoryObj<typeof meta>;

/** The first page of 30: twelve cards (hostile titles first), the facets and Load More. Guests get no "New article". */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('Showing 12 of 30')).toBeVisible();
		await expect(canvas.getByRole('heading', { level: 1, name: 'Articles' })).toBeVisible();
		await expect(canvas.getAllByRole('article')).toHaveLength(12);
		await expect(canvas.getByRole('button', { name: 'Load More' })).toBeVisible();
		await expect(canvas.queryByRole('link', { name: 'New article' })).not.toBeInTheDocument();

		await expect(requests.searches).toHaveLength(1);
		await expect(requests.last()).toMatchObject({
			page: '1',
			per_page: '12',
			sort: '-created_at',
			include_facets: 'true',
		});
	},
};

/** The placeholder the route shows while its chunk loads: filter controls and twelve card shapes. */
export const Skeleton: Story = {
	render: () => <ArticlesListSkeleton />,
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByTestId('articles-list-skeleton')).toHaveAttribute('aria-hidden', 'true');
		await expect(canvas.getAllByTestId('article-card-skeleton')).toHaveLength(12);
		await expect(requests.searches).toHaveLength(0);
	},
};

export const Guest: Story = {
	parameters: { auth: 'guest' },
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByText('Showing 12 of 30')).toBeVisible();
		await expect(within(canvasElement).queryByRole('link', { name: 'New article' })).not.toBeInTheDocument();
	},
};

/** A signed-in viewer gets "New article" beside the title. */
export const Member: Story = {
	parameters: { auth: { user: users.member } },
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('link', { name: 'New article' })).toHaveAttribute(
			'href',
			'/newarticle',
		);
	},
};

/** A shared link: search, two JLPT levels and a sort in the URL reach the request and the controls. */
export const Filtered: Story = {
	parameters: {
		router: {
			path: '/articles',
			initialEntries: ['/articles?q=%E4%BA%AC%E9%83%BD&jlpt_levels[]=n5&jlpt_levels[]=n3&sort=title_jp'],
		},
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('Showing 12 of 30 · Results for: 京都')).toBeVisible();
		await expect(canvas.getByRole('searchbox', { name: 'Search articles' })).toHaveValue('京都');
		await expect(canvas.getByRole('combobox', { name: 'Sort articles' })).toHaveValue('title_jp');
		await expect(canvas.getByRole('button', { name: 'Reset' })).toBeVisible();

		const search = requests.searches[0];
		await expect(search.get('q')).toBe('京都');
		await expect(search.getAll('jlpt_levels[]')).toEqual(['n5', 'n3']);
		await expect(search.get('sort')).toBe('title_jp');
	},
};

/** Changing a filter rewrites the URL and sends a new request carrying it; Reset clears both. */
export const FilterChangesRequest: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await canvas.findByText('Showing 12 of 30');

		await userEvent.click(canvas.getByRole('button', { name: 'N3 (21)' }));
		await waitFor(() => expect(requests.last()['jlpt_levels[]']).toBe('n3'));
		await expect(new URLSearchParams(currentLocation().split('?')[1]).getAll('jlpt_levels[]')).toEqual(['n3']);

		await userEvent.type(await canvas.findByRole('searchbox', { name: 'Search articles' }), '新幹線{Enter}');
		await waitFor(() => expect(requests.last().q).toBe('新幹線'));
		await expect(new URLSearchParams(currentLocation().split('?')[1]).get('q')).toBe('新幹線');

		// The list refetches after the search, so wait for the sort control to be back.
		await userEvent.selectOptions(
			await canvas.findByRole('combobox', { name: 'Sort articles' }, { timeout: 5000 }),
			'Oldest first',
		);
		await waitFor(() => expect(requests.last().sort).toBe('created_at'));

		await userEvent.click(await canvas.findByRole('button', { name: 'Reset' }));
		await waitFor(() => expect(currentLocation()).toBe('/articles'));
		await waitFor(() => expect(canvas.getByRole('searchbox', { name: 'Search articles' })).toHaveValue(''));
		await expect(canvas.queryByText(/Results for/)).not.toBeInTheDocument();
	},
};

/** Load More asks for page 2 with the same filters, and the cards append: 24 of 30 shown. */
export const Paged: Story = {
	parameters: { router: { path: '/articles', initialEntries: ['/articles?sort=title_jp'] } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await canvas.findByText('Showing 12 of 30');

		await userEvent.click(canvas.getByRole('button', { name: 'Load More' }));

		await expect(await canvas.findByText('Showing 24 of 30')).toBeVisible();
		await expect(canvas.getAllByRole('article')).toHaveLength(24);
		await expect(requests.searches).toHaveLength(2);
		await expect(requests.last()).toMatchObject({ page: '2', per_page: '12', sort: 'title_jp' });

		await userEvent.click(canvas.getByRole('button', { name: 'Load More' }));
		await expect(await canvas.findByText('Showing 30 of 30')).toBeVisible();
		await expect(await canvas.findByText('No more results')).toBeVisible();
		await expect(requests.last().page).toBe('3');
	},
};

/** No articles at all: the empty state, and no "No more results" line under it. */
export const Empty: Story = {
	beforeEach: ({ msw }) => {
		msw.use(articlesIndex(0));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText('No articles yet')).toBeVisible();
		await expect(canvas.queryByText('No more results')).not.toBeInTheDocument();
		await expect(canvas.queryByRole('article')).not.toBeInTheDocument();
	},
};

/** A search that matches nothing names the term and offers Reset. */
export const NoResults: Story = {
	parameters: {
		router: { path: '/articles', initialEntries: ['/articles?q=%E3%81%B5%E3%81%97%E3%81%8E%E3%81%AA'] },
	},
	beforeEach: ({ msw }) => {
		msw.use(articlesIndex(0));
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByRole('heading', { level: 2, name: /No articles match/ })).toBeVisible();
		await expect(canvas.getByText('ふしぎな')).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Reset' })).toBeVisible();
		await expect(requests.last().q).toBe('ふしぎな');
	},
};

/** The index request never answers: the header and the list skeleton, announced as loading. */
export const Loading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getArticleIndexMockHandler(async () => {
				await delay('infinite');

				return answer(new Request('http://story.test/articles'), 0);
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByRole('status')).toHaveTextContent('Loading page.');
		await expect(canvas.getByRole('heading', { level: 1, name: 'Articles' })).toBeVisible();
		await expect(canvas.getByTestId('articles-list-skeleton')).toBeInTheDocument();
	},
};

/** The index request fails with a 500: the header stays and an alert replaces the list. */
export const LoadError: Story = {
	name: 'Error',
	beforeEach: ({ msw }) => {
		msw.use(
			getArticleIndexMockHandler(() => {
				throw new Response(null, { status: 500 });
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByText(/^Error:/)).toBeVisible();
		await expect(canvas.getByRole('heading', { level: 1, name: 'Articles' })).toBeVisible();
		await expect(canvas.queryByRole('article')).not.toBeInTheDocument();
	},
};

/** One column of cards, and the filter bar stacked, at 360px. */
export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByText('Showing 12 of 30')).toBeVisible();
	},
};
