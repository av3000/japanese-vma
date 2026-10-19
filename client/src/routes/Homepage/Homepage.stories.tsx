import type { Meta, StoryObj } from '@storybook/react-vite';
import { delay } from 'msw';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { getArticleIndexMockHandler } from '@/api/generated/article/article.msw';
import { getCatalogueIndexMockHandler } from '@/api/generated/catalogue/catalogue.msw';
import { getCorpusStatsShowMockHandler } from '@/api/generated/corpus-stats/corpus-stats.msw';
import type { ArticleListResource, CatalogueListResource, CorpusStatsResource } from '@/api/generated/model';
import { latestArticles, popularCatalogues } from '@/components/features/Homepage/fixtures';
import { users } from '@/storybook/fixtures/users';
import { noAppliedArticleCriteria } from '../pageFixtures';
import Homepage from './';

/*
 * The landing page on its real route component: three sections, each with its own request through
 * the generated MSW handlers. The articles section asks for the four newest articles and the
 * catalogues section for the four most viewed public ones.
 */

const stats: CorpusStatsResource = { radicals: 214, kanjis: 13108, words: 183512, sentences: 149862 };

const articlesAnswer: ArticleListResource = {
	items: latestArticles,
	facets: [],
	applied: noAppliedArticleCriteria,
	pagination: { page: 1, per_page: 4, total: 128, last_page: 32, has_more: true },
};

const cataloguesAnswer: CatalogueListResource = {
	items: popularCatalogues,
	pagination: { page: 1, per_page: 4, total: 37, last_page: 10, has_more: true },
};

const meta = {
	title: 'Pages/Homepage',
	component: Homepage,
	parameters: { layout: 'fullscreen', router: { path: '/', initialEntries: ['/'] } },
	beforeEach: ({ msw }) => {
		msw.use(
			getCorpusStatsShowMockHandler(stats),
			getArticleIndexMockHandler(articlesAnswer),
			getCatalogueIndexMockHandler(cataloguesAnswer),
		);
	},
} satisfies Meta<typeof Homepage>;

export default meta;

type Story = StoryObj<typeof meta>;

/** The corpus tiles, the photo band, four latest articles and four popular catalogues, each with its "All" link. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('heading', { level: 1, name: 'Find your next reading' })).toBeVisible();
		await expect(await canvas.findByRole('link', { name: '13,108 Kanji' })).toHaveAttribute('href', '/kanjis');
		await expect(canvas.getByRole('link', { name: '214 Radicals' })).toHaveAttribute('href', '/radicals');

		const articles = within(await canvas.findByRole('region', { name: 'Latest articles' }));
		await expect(await articles.findAllByRole('listitem')).toHaveLength(4);
		await expect(articles.getByRole('link', { name: 'All 128 articles' })).toHaveAttribute('href', '/articles');

		const catalogues = within(await canvas.findByRole('region', { name: 'Popular catalogues' }));
		await expect(await catalogues.findAllByRole('listitem')).toHaveLength(4);
		await expect(catalogues.getByRole('link', { name: 'All 37 catalogues' })).toHaveAttribute(
			'href',
			'/catalogues',
		);
	},
};

/** The page is the same for a signed-in viewer: search and sign-up live in the Header. */
export const Member: Story = {
	parameters: { auth: { user: users.member } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(await canvas.findByRole('link', { name: '13,108 Kanji' })).toBeVisible();
		await expect(await canvas.findByRole('region', { name: 'Latest articles' })).toBeVisible();
		await expect(canvas.queryByRole('link', { name: /sign up|register/i })).not.toBeInTheDocument();
	},
};

/** Whether the articles handler still answers with a 500; the story turns it off before pressing Retry. */
let articlesFailing = true;

/** The articles request fails with a 500: that section says so and offers Retry, and the rest of the page renders. */
export const PartialFailure: Story = {
	beforeEach: ({ msw }) => {
		articlesFailing = true;

		msw.use(
			getArticleIndexMockHandler(() => {
				if (articlesFailing) {
					throw new Response(null, { status: 500 });
				}

				return articlesAnswer;
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		const articles = within(await canvas.findByRole('region', { name: 'Latest articles' }));
		await expect(await articles.findByText("Couldn't load articles")).toBeVisible();
		await expect(articles.getByRole('button', { name: 'Retry' })).toBeVisible();

		await expect(await canvas.findByRole('link', { name: '13,108 Kanji' })).toBeVisible();
		const catalogues = within(await canvas.findByRole('region', { name: 'Popular catalogues' }));
		await expect(await catalogues.findAllByRole('listitem')).toHaveLength(4);

		articlesFailing = false;
		await userEvent.click(articles.getByRole('button', { name: 'Retry' }));
		await waitFor(() => expect(articles.getAllByRole('listitem')).toHaveLength(4));
		await expect(articles.queryByText("Couldn't load articles")).not.toBeInTheDocument();
	},
};

/** Every request never answers: skeleton tiles and rows of the final size hold the space. */
export const Loading: Story = {
	beforeEach: ({ msw }) => {
		msw.use(
			getCorpusStatsShowMockHandler(async () => {
				await delay('infinite');

				return stats;
			}),
			getArticleIndexMockHandler(async () => {
				await delay('infinite');

				return articlesAnswer;
			}),
			getCatalogueIndexMockHandler(async () => {
				await delay('infinite');

				return cataloguesAnswer;
			}),
		);
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('heading', { level: 1, name: 'Find your next reading' })).toBeVisible();
		await waitFor(() => expect(canvas.getAllByTestId('corpus-tile-skeleton')).toHaveLength(4));
		await expect(canvas.getAllByTestId('compact-list-skeleton')).toHaveLength(8);
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('link', { name: '13,108 Kanji' })).toBeVisible();
		await expect(await within(canvasElement).findByRole('region', { name: 'Popular catalogues' })).toBeVisible();
	},
};

/** Two columns of tiles, and the two lists side by side. */
export const Tablet: Story = {
	globals: { viewport: { value: 'sm' } },
	play: async ({ canvasElement }) => {
		await expect(await within(canvasElement).findByRole('link', { name: '13,108 Kanji' })).toBeVisible();
		await expect(await within(canvasElement).findByRole('region', { name: 'Latest articles' })).toBeVisible();
	},
};
