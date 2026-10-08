import type { Meta, StoryObj } from '@storybook/react';
import { expect, userEvent, within } from '@storybook/test';
import type { CatalogueArticleItem } from '@/api/catalogues/catalogues';
import { catalogueItemsQueryKey } from '@/api/catalogues/items';
import {
	kanjiRows,
	radicalRows,
	repeatRows,
	sentenceRows,
	wordRows,
} from '@/components/shared/DataTable/DataTable.fixtures';
import type { CatalogueFamily } from '@/shared/constants/catalogues';
import { pageSeed, SeededQueryClient, type QuerySeed } from '@/test/seededQueryClient';
import { CatalogueItems } from './';

const UUID = 'c1c1c1c1-0000-4000-8000-000000000001';
const TYPES: Record<CatalogueFamily, number> = { radicals: 5, kanji: 6, words: 7, sentences: 8, articles: 9 };

/** The first page of a family's items, with the server's total. */
const firstPage = <Row,>(family: CatalogueFamily, rows: Row[], total = rows.length): QuerySeed =>
	pageSeed(catalogueItemsQueryKey(family, UUID) ?? [], {
		items: rows,
		pagination: {
			page: 1,
			per_page: 25,
			total,
			last_page: Math.ceil(total / 25) || 1,
			has_more: total > rows.length,
		},
	});

const articleItems: CatalogueArticleItem[] = Array.from({ length: 3 }, (_, index) => ({
	id: index + 1,
	uuid: `e2f6d1c0-1111-4222-8333-44445555666${index}`,
	title_jp: ['ビシバンカの日', '3時間以内の主な地震（震度3以上）', '長'.repeat(120)][index],
	saves_count: 1,
	hashtags: [{ id: index, content: 'news' }] as CatalogueArticleItem['hashtags'],
	engagement: {
		likes_count: String(index),
		views_count: String(index * 40),
		downloads_count: '0',
		comments_count: '0',
	} as CatalogueArticleItem['engagement'],
}));

const meta = {
	title: 'Features/Catalogues/CatalogueItems',
	component: CatalogueItems,
	tags: ['autodocs'],
	parameters: { layout: 'padded', seeds: [] as QuerySeed[] },
	args: { catalogueUuid: UUID, catalogueType: TYPES.kanji, payloadItems: [], isOwner: false, showSave: false },
	decorators: [
		(Story, context) => (
			<SeededQueryClient seeds={(context.parameters.seeds as QuerySeed[]) ?? []}>
				<div style={{ maxWidth: 720 }}>
					<Story />
				</div>
			</SeededQueryClient>
		),
	],
} satisfies Meta<typeof CatalogueItems>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Kanji: Story = {
	parameters: { seeds: [firstPage('kanji', kanjiRows)] },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('table', { name: 'Kanji' })).toBeVisible();
	},
};

/** 500 saved kanji: the first 25 rows, numbered pages and "Load all". */
export const Kanji500: Story = {
	parameters: { seeds: [firstPage('kanji', repeatRows(kanjiRows, 25), 500)] },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('navigation', { name: 'Kanji pages' })).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Load all 500 kanji' })).toBeVisible();
		await expect(canvas.getByText('Showing 1–25 of 500 kanji')).toBeVisible();
	},
};

export const Words500: Story = {
	args: { catalogueType: TYPES.words },
	parameters: { seeds: [firstPage('words', repeatRows(wordRows, 25), 500)] },
};

export const Radicals500: Story = {
	args: { catalogueType: TYPES.radicals },
	parameters: { seeds: [firstPage('radicals', repeatRows(radicalRows, 25), 500)] },
};

export const Sentences500: Story = {
	args: { catalogueType: TYPES.sentences },
	parameters: { seeds: [firstPage('sentences', repeatRows(sentenceRows, 25), 500)] },
};

export const Articles: Story = { args: { catalogueType: TYPES.articles, payloadItems: articleItems } };

export const EmptyForAGuest: Story = {
	parameters: { seeds: [firstPage('kanji', [])] },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('This catalogue has no items yet.')).toBeVisible();
	},
};

export const EmptyForTheOwner: Story = {
	args: { isOwner: true, catalogueType: TYPES.words },
	parameters: { seeds: [firstPage('words', [])] },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText(/Use Save to a catalogue on any word page/)).toBeVisible();
	},
};

/** The owner switches on Manage items: a Remove button on every row, confirmed before it acts. */
export const OwnerManagingItems: Story = {
	args: { isOwner: true, showSave: true },
	parameters: { seeds: [firstPage('kanji', kanjiRows)] },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await userEvent.click(canvas.getByRole('button', { name: 'Manage items' }));
		await expect(canvas.getByRole('button', { name: 'Done managing' })).toHaveAttribute('aria-pressed', 'true');
		await expect(
			canvas.getByRole('button', { name: `Remove ${kanjiRows[0].character} from this catalogue` }),
		).toBeVisible();
	},
};

export const OwnerManagingArticlesMobile: Story = {
	args: { isOwner: true, catalogueType: TYPES.articles, payloadItems: articleItems },
	parameters: { viewport: { defaultViewport: 'mobile1' } },
	play: async ({ canvasElement }) => {
		await userEvent.click(within(canvasElement).getByRole('button', { name: 'Manage items' }));
	},
};
