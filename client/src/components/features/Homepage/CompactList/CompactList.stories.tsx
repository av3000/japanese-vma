import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, within } from 'storybook/test';
import { LONGEST_JAPANESE_TITLE, latestArticles, popularCatalogues } from '../fixtures';
import { CompactListLink, CompactListRow, CompactListSection } from './index';

/*
 * The shell under "Latest articles" and "Popular catalogues": heading row, "All" link and one of
 * four states. These stories give it plain rows; the two lists' own rows are in their stories.
 */

const meta = {
	title: 'Features/Homepage/CompactList',
	component: CompactListSection,
	parameters: { layout: 'padded' },
	args: {
		title: 'Latest articles',
		allHref: '/articles',
		allNoun: 'articles',
		total: 128,
		status: 'success',
		isEmpty: false,
		emptyText: 'No articles yet',
		errorText: 'Couldn’t load articles',
		onRetry: fn(),
	},
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '36rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof CompactListSection>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Articles: Story = {
	render: (args) => (
		<CompactListSection {...args}>
			{latestArticles.map((article) => (
				<CompactListRow key={article.uuid}>
					<CompactListLink to={`/articles/${article.uuid}`} lang="ja">
						{article.title_jp}
					</CompactListLink>
				</CompactListRow>
			))}
		</CompactListSection>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('heading', { level: 2, name: 'Latest articles' })).toBeVisible();
		await expect(canvas.getByRole('link', { name: 'All 128 articles' })).toHaveAttribute('href', '/articles');
		await expect(canvas.getAllByRole('listitem')).toHaveLength(latestArticles.length);
		await expect(canvas.getByRole('link', { name: '新しい駅が開業' })).toHaveAttribute(
			'href',
			'/articles/article-2',
		);
	},
};

export const Catalogues: Story = {
	args: {
		title: 'Popular catalogues',
		allHref: '/catalogues',
		allNoun: 'catalogues',
		total: 36,
		emptyText: 'No public catalogues yet',
		errorText: 'Couldn’t load catalogues',
	},
	render: (args) => (
		<CompactListSection {...args}>
			{popularCatalogues.map((catalogue) => (
				<CompactListRow key={catalogue.uuid}>
					<CompactListLink to={`/catalogues/${catalogue.uuid}`}>{catalogue.title}</CompactListLink>
					<span>{catalogue.items_count} items</span>
				</CompactListRow>
			))}
		</CompactListSection>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('link', { name: 'All 36 catalogues' })).toHaveAttribute('href', '/catalogues');
		await expect(canvas.getAllByRole('listitem')).toHaveLength(popularCatalogues.length);
	},
};

/** Hostile: very long titles wrap inside the row, and the whole row is still one link. */
export const LongTitles: Story = {
	render: (args) => (
		<CompactListSection {...args}>
			{[LONGEST_JAPANESE_TITLE, popularCatalogues[1].title, 'unbreakable-title-'.repeat(8)].map((title) => (
				<CompactListRow key={title}>
					<CompactListLink to="/articles/long" lang="ja">
						{title}
					</CompactListLink>
				</CompactListRow>
			))}
		</CompactListSection>
	),
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getAllByRole('link', { name: /./ })).toHaveLength(4);
	},
};

/** Loading: four skeleton rows of the final height, hidden from assistive technology. */
export const Loading: Story = {
	args: { status: 'pending', total: undefined },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('link', { name: 'All articles' })).toBeVisible();
		await expect(canvas.getAllByTestId('compact-list-skeleton')).toHaveLength(4);
	},
};

/** Nothing to list: one line, and the "All" link still goes to the full list. */
export const Empty: Story = {
	args: { isEmpty: true, total: 0 },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('No articles yet')).toBeVisible();
		await expect(canvas.queryByRole('listitem')).not.toBeInTheDocument();
	},
};

/** A failure shows a fixed line and Retry, never the raw message. */
export const ErrorState: Story = {
	name: 'Error',
	args: { status: 'error', total: undefined },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('Couldn’t load articles')).toBeVisible();
		await userEvent.click(canvas.getByRole('button', { name: 'Retry' }));
		await expect(args.onRetry).toHaveBeenCalledTimes(1);
	},
};
