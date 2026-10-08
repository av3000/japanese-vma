import type * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, userEvent, waitFor, within } from '@storybook/test';
import { articleKanjisQueryKey, articleWordsQueryKey } from '@/api/articles/attachments';
import type { MappedArticle } from '@/api/articles/details';
import { getCommentsQueryKey } from '@/api/comments';
import type { CommentResource } from '@/api/generated/model/commentResource';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import type { ProcessingStatusResource } from '@/api/generated/model/processingStatusResource';
import { kanjiRows, repeatRows, wordRows } from '@/components/shared/DataTable/DataTable.fixtures';
import { AuthContext } from '@/providers/contexts/auth-provider';
import { DEFAULT_SOCKET_CONTEXT, SocketContext } from '@/providers/contexts/socket-provider';
import { pageSeed, SeededQueryClient, type QuerySeed } from '@/test/seededQueryClient';
import ArticleContent from './';

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
const signedIn = (id: number, isAdmin = false): AuthValue => ({
	...guest,
	isAuthenticated: true,
	token: 'story-token',
	user: { id, uuid: `user-${id}`, name: 'Hanako', email: 'hanako@example.com', roles: [], isAdmin },
});
const AUTHOR_ID = 7;

const UUID = '8b0c1d2e-3f40-4a51-8b62-7c83d94ea5f6';
const PARAGRAPHS = [
	'ロシアによる軍事侵攻が長期化する中、ウクライナでは21日、伝統衣装を着て1日を過ごす「ビシバンカの日」を迎え、多くの人が伝統衣装を着て街に繰り出し、ウクライナ人としての結束を再確認していました。',
	'「ビシバンカ」は幾何学模様や植物などの刺しゅうが施されたウクライナの伝統衣装で、毎年5月の第3木曜日は「ビシバンカの日」とされ、多くの人が伝統衣装を着て1日を過ごします。',
];

const article = (overrides: Partial<MappedArticle> = {}): MappedArticle =>
	({
		id: 321,
		uid: UUID,
		uuid: UUID,
		entity_type_uid: 'article-entity-type',
		title_jp: 'ウクライナで「ビシバンカの日」市民が伝統衣装で結束を確認',
		title_en: 'Ukrainians mark Vyshyvanka Day in traditional dress',
		content_jp: PARAGRAPHS.join('\n\n'),
		content_en:
			'As the invasion drags on, Ukraine marked Vyshyvanka Day on the 21st.\nMany wore embroidered shirts.',
		source_link: 'https://www3.nhk.or.jp/news/html/20260521/k10014000000000.html',
		publicity: 1,
		status: 0,
		jlpt_levels: { n1: 4, n2: 9, n3: 12, n4: 8, n5: 6, uncommon: 3 },
		author: { id: AUTHOR_ID, name: 'Aki Tanaka', uuid: 'author-uuid' },
		hashtags: [
			{ id: 1, content: 'ukraine' },
			{ id: 2, content: 'culture' },
		],
		created_at: '2026-05-25T10:00:00+00:00',
		updated_at: '2026-05-25T10:00:00+00:00',
		engagement: { is_liked_by_viewer: false, likes_count: 3, views_count: 128, downloads_count: 0 },
		kanjis: [],
		words: [],
		processing_status: {
			id: 1,
			entity_id: UUID,
			type: 'article_content_processing',
			status: ProcessingStatus.completed,
			sequence: 1,
			attempt: 1,
			max_attempts: 3,
			metadata: {},
			created_at: '2026-05-25T10:00:00+00:00',
			updated_at: '2026-05-25T10:00:42+00:00',
		},
		displayName: 'Aki Tanaka',
		formattedDate: '5/25/2026',
		...overrides,
	}) as MappedArticle;

const withStatus = (status: ProcessingStatus, attempt = 1) => ({
	...(article().processing_status as ProcessingStatusResource),
	status,
	attempt,
});

const pageOf = <Row,>(items: Row[], total = items.length) => ({
	items,
	pagination: { page: 1, per_page: 20, total, last_page: Math.ceil(total / 20) || 1, has_more: total > items.length },
});

const comment = (index: number): CommentResource =>
	({
		id: index,
		uuid: `comment-${index}`,
		entity_uuid: UUID,
		entity_type_uuid: '' as CommentResource['entity_type_uuid'],
		entity_type_label: 'article',
		author: { id: 100 + index, name: `Reader ${index}`, uuid: `reader-${index}` },
		content: 'とても分かりやすい記事でした。ありがとうございます。',
		parent_comment_id: null,
		is_reply: false,
		likes_count: index % 3,
		viewer: { is_liked: false, can_edit: false, can_delete: false },
		replies_count: 0,
		replies: [],
		created_at: '2026-05-26T10:00:00+00:00',
		updated_at: '2026-05-26T10:00:00+00:00',
	}) as CommentResource;

const seeds = (options: { kanji?: number; words?: number; comments?: number } = {}): QuerySeed[] => {
	const kanji = options.kanji ?? 42;
	const words = options.words ?? 118;
	const comments = options.comments ?? 2;

	return [
		pageSeed(articleKanjisQueryKey(UUID), pageOf(repeatRows(kanjiRows, Math.min(kanji, 20)), kanji)),
		pageSeed(articleWordsQueryKey(UUID), pageOf(repeatRows(wordRows, Math.min(words, 20)), words)),
		{
			queryKey: getCommentsQueryKey('article', UUID),
			data: pageOf(
				Array.from({ length: Math.min(comments, 20) }, (_, index) => comment(index + 1)),
				comments,
			),
		},
	];
};

interface StoryParams {
	auth?: AuthValue;
	seeds?: QuerySeed[];
}

const meta = {
	title: 'Pages/ArticleDetails',
	component: ArticleContent,
	tags: ['autodocs'],
	parameters: { layout: 'fullscreen' },
	args: { article: article() },
	decorators: [
		(Story, context) => {
			const params = context.parameters as StoryParams;

			return (
				<AuthContext.Provider value={params.auth ?? guest}>
					<SocketContext.Provider
						value={{ ...DEFAULT_SOCKET_CONTEXT, isConnected: true, connectionStatus: 'connected' }}
					>
						<SeededQueryClient seeds={params.seeds ?? seeds()}>
							<Story />
						</SeededQueryClient>
					</SocketContext.Provider>
				</AuthContext.Provider>
			);
		},
	],
} satisfies Meta<typeof ArticleContent>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Guest: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getAllByRole('heading', { level: 1 })).toHaveLength(1);
		await expect(canvas.getByRole('complementary', { name: 'About this article' })).toBeVisible();
		await expect(
			within(canvas.getByRole('complementary', { name: 'About this article' })).getAllByText('42')[0],
		).toBeVisible();
		await expect(canvas.queryByText('Your article')).not.toBeInTheDocument();
		await expect(canvas.queryByText('Save to a catalogue')).not.toBeInTheDocument();
	},
};

export const GuestMobile: Story = { parameters: { viewport: { defaultViewport: 'mobile1' } } };

export const GuestTablet: Story = { parameters: { viewport: { defaultViewport: 'tablet' } } };

export const Owner: Story = {
	parameters: { auth: signedIn(AUTHOR_ID) },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('region', { name: 'Your article' })).toBeVisible();
		await expect(canvas.getByText('Public')).toBeVisible();
		await expect(canvas.getByRole('button', { name: /Save to a catalogue/ })).toBeVisible();
	},
};

export const OwnerMobile: Story = {
	parameters: { auth: signedIn(AUTHOR_ID), viewport: { defaultViewport: 'mobile1' } },
};

export const Admin: Story = {
	parameters: { auth: signedIn(99, true) },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('button', { name: 'Review' })).toBeVisible();
		await expect(canvas.queryByRole('region', { name: 'Your article' })).not.toBeInTheDocument();
	},
};

export const PrivateForTheOwner: Story = {
	args: { article: article({ publicity: 0 }) },
	parameters: { auth: signedIn(AUTHOR_ID) },
};

export const Pending: Story = {
	args: { article: article({ processing_status: withStatus(ProcessingStatus.pending) }) },
	parameters: { seeds: seeds({ kanji: 0, words: 0 }) },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getAllByText('Counting…')).toHaveLength(2);
	},
};

export const Processing: Story = {
	args: { article: article({ processing_status: withStatus(ProcessingStatus.processing) }) },
	parameters: { seeds: seeds({ kanji: 0, words: 0 }) },
};

/** Failed with zero kanji, as the owner sees it: how to run the analysis again. */
export const FailedForTheOwner: Story = {
	args: {
		article: article({
			processing_status: withStatus(ProcessingStatus.failed, 3),
			jlpt_levels: { n1: 0, n2: 0, n3: 0, n4: 0, n5: 0, uncommon: 0 },
		}),
	},
	parameters: { auth: signedIn(AUTHOR_ID), seeds: seeds({ kanji: 0, words: 0 }) },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText(/runs it again/)).toBeVisible();
	},
};

/** A 255-character title, a 2,000-character body with a 400-character run and a 300-character URL. */
export const HostileContentMobile: Story = {
	args: {
		article: article({
			title_jp: '長'.repeat(255),
			title_en: 'L'.repeat(255),
			content_jp: [
				...Array.from({ length: 9 }, (_, index) => PARAGRAPHS[index % 2]),
				'あ'.repeat(400),
				`https://example.com/${'a'.repeat(280)}`,
			].join('\n'),
			hashtags: Array.from({ length: 10 }, (_, index) => ({
				id: index,
				content: `tag${'x'.repeat(45)}${index}`,
			})) as MappedArticle['hashtags'],
		}),
	},
	parameters: { viewport: { defaultViewport: 'mobile1' }, seeds: seeds({ kanji: 500, words: 900, comments: 200 }) },
	play: async ({ canvasElement }) => {
		await expect(canvasElement.scrollWidth).toBeLessThanOrEqual(canvasElement.clientWidth + 1);
	},
};

export const TwoHundredComments: Story = {
	parameters: { seeds: seeds({ comments: 200 }) },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Showing 20 of 200 comments')).toBeVisible();
	},
};

/** "See all" opens the full list in a modal with numbered pages and Load all (#522). */
export const AllKanjiModal: Story = {
	parameters: { auth: signedIn(AUTHOR_ID), seeds: seeds({ kanji: 171 }) },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await userEvent.click(canvas.getByRole('button', { name: 'See all 171 kanji' }));

		const dialog = within(canvasElement.ownerDocument.getElementById('article-kanji-modal') as HTMLElement);
		// The dialog fades in; wait for it rather than checking mid-transition.
		await waitFor(() => expect(dialog.getByRole('table', { name: 'Kanji' })).toBeVisible());
		await expect(dialog.getByRole('navigation', { name: 'Kanji pages' })).toBeVisible();
		await expect(dialog.getByRole('button', { name: 'Load all 171 kanji' })).toBeVisible();
	},
};
