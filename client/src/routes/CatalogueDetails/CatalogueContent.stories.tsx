import type * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import type { CatalogueArticleItem } from '@/api/catalogues/catalogues';
import type { MappedCatalogue } from '@/api/catalogues/details';
import { catalogueItemsQueryKey } from '@/api/catalogues/items';
import { getCommentsQueryKey } from '@/api/comments';
import { kanjiRows, repeatRows, sentenceRows, wordRows } from '@/components/shared/DataTable/DataTable.fixtures';
import { AuthContext } from '@/providers/contexts/auth-provider';
import type { CatalogueFamily } from '@/shared/constants/catalogues';
import { infiniteSeed, SeededQueryClient, type QuerySeed } from '@/test/seededQueryClient';
import CatalogueContent from './CatalogueContent';

type AuthValue = NonNullable<React.ComponentProps<typeof AuthContext.Provider>['value']>;

const noop = async () => undefined;
const guest: AuthValue = {
	user: null,
	isAuthenticated: false,
	isLoading: false,
	sessionExpired: false,
	token: null,
	login: noop,
	register: noop,
	logout: noop,
	clearSessionExpired: () => undefined,
};
const OWNER_ID = 7;
const owner: AuthValue = {
	...guest,
	isAuthenticated: true,
	token: 'story-token',
	user: { id: OWNER_ID, uuid: 'owner-uuid', name: 'Aki Tanaka', email: 'aki@example.com', roles: [], isAdmin: false },
};

const UUID = '6c1d2e3f-4a5b-4c6d-8e7f-90a1b2c3d4e5';
const TYPES: Record<CatalogueFamily, number> = { radicals: 5, kanji: 6, words: 7, sentences: 8, articles: 9 };

const catalogue = (overrides: Partial<MappedCatalogue> = {}): MappedCatalogue =>
	({
		id: 55,
		uuid: UUID,
		type: TYPES.kanji,
		type_label: 'Kanji',
		title: 'N3 kanji for the news',
		description: 'Kanji I keep meeting in NHK Easy articles.',
		publicity: 1,
		owner: { id: OWNER_ID, uuid: 'owner-uuid', name: 'Aki Tanaka' },
		items_count: 42,
		hashtags: [
			{ id: 1, content: 'jlpt-n3' },
			{ id: 2, content: 'news' },
		],
		engagement: {
			likes_count: 4,
			views_count: 128,
			downloads_count: 2,
			comments_count: 0,
			is_liked_by_viewer: false,
		},
		jlpt_levels: { n1: 2, n2: 8, n3: 21, n4: 7, n5: 4, uncommon: 0 },
		items: [],
		created_at: '2026-04-01T12:00:00+00:00',
		updated_at: '2026-04-02T12:00:00+00:00',
		displayName: 'Aki Tanaka',
		formattedDate: '4/1/2026',
		...overrides,
	}) as MappedCatalogue;

const firstPage = <Row,>(family: CatalogueFamily, rows: Row[], total = rows.length): QuerySeed =>
	infiniteSeed(catalogueItemsQueryKey(family, UUID) ?? [], [
		{
			items: rows,
			pagination: {
				page: 1,
				per_page: 25,
				total,
				last_page: Math.ceil(total / 25) || 1,
				has_more: total > rows.length,
			},
		},
	]);

const noComments: QuerySeed = {
	queryKey: getCommentsQueryKey('catalogue', UUID),
	data: { items: [], pagination: { page: 1, per_page: 20, total: 0, last_page: 1, has_more: false } },
};

const articleItems = Array.from({ length: 3 }, (_, index) => ({
	id: index + 1,
	uuid: `e2f6d1c0-1111-4222-8333-44445555666${index}`,
	title_jp: ['ビシバンカの日', '3時間以内の主な地震（震度3以上）', '長'.repeat(120)][index],
	saves_count: 1,
	hashtags: [{ id: index, content: 'news' }],
	engagement: {
		likes_count: String(index),
		views_count: String(index * 40),
		downloads_count: '0',
		comments_count: '0',
	},
})) as unknown as CatalogueArticleItem[];

interface StoryParams {
	auth?: AuthValue;
	seeds?: QuerySeed[];
}

const meta = {
	title: 'Pages/CatalogueDetails',
	component: CatalogueContent,
	tags: ['autodocs'],
	parameters: { layout: 'fullscreen' },
	args: { catalogue: catalogue() },
	decorators: [
		(Story, context) => {
			const params = context.parameters as StoryParams;

			return (
				<AuthContext.Provider value={params.auth ?? guest}>
					<SeededQueryClient seeds={params.seeds ?? [firstPage('kanji', kanjiRows, 42), noComments]}>
						<Story />
					</SeededQueryClient>
				</AuthContext.Provider>
			);
		},
	],
} satisfies Meta<typeof CatalogueContent>;

export default meta;

type Story = StoryObj<typeof meta>;

/** A Study-eligible kanji catalogue as a visitor sees it: Study is offered, no owner actions. */
export const Guest: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getAllByRole('heading', { level: 1 })).toHaveLength(1);
		await expect(canvas.getByRole('link', { name: 'Study' })).toBeVisible();
		await expect(canvas.queryByText('Edit catalogue')).not.toBeInTheDocument();
	},
};

export const GuestMobile: Story = { parameters: { viewport: { defaultViewport: 'mobile1' } } };

export const Owner: Story = {
	parameters: { auth: owner },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('region', { name: 'Your catalogue' })).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Manage items' })).toBeVisible();
		await expect(canvas.getByText('Public')).toBeVisible();
	},
};

export const OwnerMobile: Story = { parameters: { auth: owner, viewport: { defaultViewport: 'mobile1' } } };

/** Empty and Study-eligible: no Study button, a hint for the owner. */
export const EmptyForTheOwner: Story = {
	args: { catalogue: catalogue({ items_count: 0, jlpt_levels: null }) },
	parameters: { auth: owner, seeds: [firstPage('kanji', []), noComments] },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.queryByRole('link', { name: 'Study' })).not.toBeInTheDocument();
		await expect(canvas.getByText('Add kanji, words or radicals to study this catalogue.')).toBeVisible();
	},
};

export const Kanji500: Story = {
	args: { catalogue: catalogue({ items_count: 500 }) },
	parameters: { seeds: [firstPage('kanji', repeatRows(kanjiRows, 25), 500), noComments] },
};

export const Words500Private: Story = {
	args: { catalogue: catalogue({ type: TYPES.words, type_label: 'Words', items_count: 500, publicity: 0 }) },
	parameters: { auth: owner, seeds: [firstPage('words', repeatRows(wordRows, 25), 500), noComments] },
};

export const Sentences: Story = {
	args: {
		catalogue: catalogue({ type: TYPES.sentences, type_label: 'Sentences', items_count: 6, jlpt_levels: null }),
	},
	parameters: { seeds: [firstPage('sentences', sentenceRows), noComments] },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).queryByRole('link', { name: 'Study' })).not.toBeInTheDocument();
	},
};

export const Articles: Story = {
	args: {
		catalogue: catalogue({
			type: TYPES.articles,
			type_label: 'Articles',
			items_count: articleItems.length,
			items: articleItems as unknown as MappedCatalogue['items'],
			jlpt_levels: null,
		}),
	},
	parameters: { seeds: [noComments] },
};

/** A 255-character title with no spaces and no description, at 360px. */
export const LongestTitleMobile: Story = {
	args: { catalogue: catalogue({ title: 'L'.repeat(255), description: null }) },
	parameters: { viewport: { defaultViewport: 'mobile1' } },
	play: async ({ canvasElement }) => {
		await expect(canvasElement.scrollWidth).toBeLessThanOrEqual(canvasElement.clientWidth + 1);
	},
};
