import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import {
	latestArticles,
	libraryArticles,
	libraryCatalogues,
	popularCatalogues,
} from '@/components/features/Homepage/fixtures';
import { PageHeader } from '@/components/shared/PageHeader';
import { ArticleCard } from './ArticleCard';
import { CatalogueCard } from './CatalogueCard';
import { LibraryCardGrid, LibraryEmptyState, LibraryPage } from './LibraryLayout';

const ARTICLES = [
	libraryArticles.longest,
	...latestArticles.slice(1),
	libraryArticles.zeroEverything,
	libraryArticles.processing,
];
const CATALOGUES = [...Object.values(libraryCatalogues), ...popularCatalogues.slice(1)];

const meta = {
	title: 'Features/LibraryCards/LibraryPage',
	component: LibraryEmptyState,
	parameters: { layout: 'fullscreen' },
	args: { title: 'No articles match', term: '氾濫', hint: 'Try a shorter search, or clear the filters.' },
	render: (args) => (
		<LibraryPage>
			<PageHeader title="Articles" meta="Showing 0 of 0" />
			<LibraryEmptyState {...args} />
		</LibraryPage>
	),
} satisfies Meta<typeof LibraryEmptyState>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Hostile articles in the grid: rows keep their stats footers aligned. */
export const ArticleGrid: Story = {
	render: () => (
		<LibraryPage>
			<PageHeader title="Articles" meta={`Showing ${ARTICLES.length} of ${ARTICLES.length}`} />
			<LibraryCardGrid>
				{ARTICLES.map((article) => (
					<ArticleCard key={article.uuid} article={article} />
				))}
			</LibraryCardGrid>
		</LibraryPage>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getAllByRole('heading', { level: 1 })).toHaveLength(1);
		await expect(canvas.getAllByRole('link')).toHaveLength(ARTICLES.length);
	},
};

export const CatalogueGrid: Story = {
	render: () => (
		<LibraryPage>
			<PageHeader title="Catalogues" meta={`Showing ${CATALOGUES.length} of ${CATALOGUES.length}`} />
			<LibraryCardGrid>
				{CATALOGUES.map((catalogue) => (
					<CatalogueCard key={catalogue.uuid} catalogue={catalogue} />
				))}
			</LibraryCardGrid>
		</LibraryPage>
	),
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getAllByRole('link')).toHaveLength(CATALOGUES.length);
	},
};

export const EmptySearch: Story = {
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('heading', { level: 2 })).toHaveTextContent(
			'No articles match “氾濫”',
		);
	},
};

export const EmptyCatalogues: Story = {
	args: { title: 'No public catalogues yet', term: undefined, hint: 'Catalogues people share publicly appear here.' },
};
