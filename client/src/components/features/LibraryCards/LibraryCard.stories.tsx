import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { makeHashtags } from '@/components/features/Homepage/fixtures';
import { CardCover } from './CardCover';
import {
	CardDate,
	CardOwner,
	CardStats,
	CardSubtitle,
	CardTags,
	CardTitle,
	LibraryCard,
	MAX_CARD_TAGS,
} from './LibraryCard';
import { LibraryCardSkeleton } from './LibraryCardSkeleton';

/*
 * The base card the Article and Catalogue cards are built from, and the skeleton that holds its
 * place while it loads. `ArticleCard` and `CatalogueCard` show the finished cards.
 */

const meta = {
	title: 'Features/LibraryCards/LibraryCard',
	component: LibraryCard,
	parameters: { layout: 'padded' },
	args: { children: null },
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '22rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof LibraryCard>;

export default meta;

type Story = StoryObj<typeof meta>;

const tags = makeHashtags(['#news', '#tokyo', '#education', '#economy', '#policy']);

/** Cover, then date, title link, subtitle, owner, tags and stats, each from its own part. */
export const Default: Story = {
	render: () => (
		<LibraryCard cover={<CardCover glyph="京" caption="Article" />}>
			<CardDate dateTime="2026-09-24">24 Sep 2026</CardDate>
			<CardTitle to="/articles/example" lang="ja">
				春の京都を歩く
			</CardTitle>
			<CardSubtitle>Walking through Kyoto in spring</CardSubtitle>
			<CardOwner name="Hanako" />
			<CardTags tags={tags} />
			<CardStats
				stats={[
					{ kind: 'views', count: 1284 },
					{ kind: 'comments', count: 37 },
					{ kind: 'likes', count: 212 },
				]}
			/>
		</LibraryCard>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('link', { name: '春の京都を歩く' })).toHaveAttribute('href', '/articles/example');
		await expect(within(canvas.getByRole('list', { name: 'Tags' })).getAllByRole('listitem')).toHaveLength(
			MAX_CARD_TAGS + 1,
		);
		await expect(canvas.getByRole('list', { name: 'Stats' })).toHaveTextContent('1,284 views');
	},
};

/** Without a `cover` the card is the text column alone, at every width. */
export const NoCover: Story = {
	render: () => (
		<LibraryCard>
			<CardTitle to="/community/posts/example">A post with no cover</CardTitle>
			<CardSubtitle lines={2}>
				The text column fills the card, and the subtitle may run to two lines.
			</CardSubtitle>
			<CardStats stats={[{ kind: 'comments', count: 3 }]} />
		</LibraryCard>
	),
};

/** The loading placeholder for each card kind, side by side with the cover footprint of the real card. */
export const Skeleton: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '1rem' }}>
			<LibraryCardSkeleton kind="article" />
			<LibraryCardSkeleton kind="catalogue" />
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		// Both are hidden from assistive technology: a screen reader hears nothing for a placeholder.
		await expect(canvas.getByTestId('article-card-skeleton')).toHaveAttribute('aria-hidden', 'true');
		await expect(canvas.getByTestId('catalogue-card-skeleton')).toHaveAttribute('aria-hidden', 'true');
		await expect(canvas.queryByRole('link')).not.toBeInTheDocument();
	},
};
