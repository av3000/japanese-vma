/*
 * Typed fixtures and request helpers shared by the route stories (`Pages/…`). The routes load their
 * record through the generated MSW handlers, so a story hands a handler one of these. Not imported
 * by application code.
 */
import type { ArticleDetailResource } from '@/api/generated/model/articleDetailResource';
import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';
import { ProcessingStatus } from '@/api/generated/model/processingStatus';
import type { SentenceResource } from '@/api/generated/model/sentenceResource';
import { sentenceShort } from '@/components/features/japanese/related/dictionaryDetail.fixtures';
import { users } from '@/storybook/fixtures/users';

/** The signed-in member as the API reports an author, so a record can belong to them. */
export const MEMBER_AS_AUTHOR = { id: users.member.id, uuid: users.member.uuid, name: users.member.name };

/** Another member's author block, for a record the signed-in member does not own. */
export const OTHER_AS_AUTHOR = { id: users.other.id, uuid: users.other.uuid, name: users.other.name };

export const ARTICLE_UUID = '8b0c1d2e-3f40-4a51-8b62-7c83d94ea5f6';
export const CATALOGUE_UUID = '6c1d2e3f-4a5b-4c6d-8e7f-90a1b2c3d4e5';
export const SENTENCE_UUID = '7b292a92-c078-4ba0-9ca0-78bb1c9d11c1';
export const POST_UUID = 'post-41';

/** An article the signed-in member wrote, fully processed. */
export const articleDetail = (overrides: Partial<ArticleDetailResource> = {}): ArticleDetailResource => ({
	id: 321,
	uid: ARTICLE_UUID,
	entity_type_uid: 'article-entity-type',
	title_jp: '新宿駅の新しい改札が完成',
	title_en: 'New ticket gates completed at Shinjuku Station',
	content_jp:
		'ＪＲ新宿駅で、新しい改札が完成しました。東口と西口をつなぐ通路が広くなり、乗り換えがしやすくなります。工事は3年かかりました。',
	content_en:
		'New ticket gates opened at JR Shinjuku Station.\nThe passage between the east and west exits is wider.',
	source_link: 'https://www3.nhk.or.jp/news/easy/k10014322571000/k10014322571000.html',
	publicity: 1,
	status: 0,
	jlpt_levels: { n1: 4, n2: 9, n3: 12, n4: 8, n5: 6, uncommon: 3 },
	author: MEMBER_AS_AUTHOR,
	hashtags: [
		{ id: 1, content: 'shinjuku', created_at: null, updated_at: null },
		{ id: 2, content: 'station', created_at: null, updated_at: null },
	],
	created_at: '2026-05-25T10:00:00+00:00',
	updated_at: '2026-05-25T10:00:00+00:00',
	engagement: {
		is_liked_by_viewer: false,
		likes_count: 3,
		views_count: 128,
		downloads_count: 0,
	} as unknown as ArticleDetailResource['engagement'],
	kanjis: [],
	words: [],
	processing_status: {
		id: 1,
		entity_id: ARTICLE_UUID,
		type: 'article_content_processing',
		status: ProcessingStatus.completed,
		sequence: 1,
		attempt: 1,
		max_attempts: 3,
		metadata: {},
		created_at: '2026-05-25T10:00:00+00:00',
		updated_at: '2026-05-25T10:00:42+00:00',
	} as ArticleDetailResource['processing_status'],
	...overrides,
});

/** A kanji catalogue the signed-in member owns, with items, so its type is locked. */
export const catalogueDetail = (overrides: Partial<CatalogueDetailResource> = {}): CatalogueDetailResource =>
	({
		id: 55,
		uuid: CATALOGUE_UUID,
		type: 6,
		type_label: 'Kanji',
		title: 'N3 kanji for the news',
		description: 'Kanji I keep meeting in NHK Easy articles.',
		publicity: 1,
		owner: MEMBER_AS_AUTHOR,
		items_count: 42,
		hashtags: [
			{ id: 1, content: 'jlpt-n3', created_at: null, updated_at: null },
			{ id: 2, content: 'news', created_at: null, updated_at: null },
		],
		engagement: {
			likes_count: '4',
			views_count: '128',
			downloads_count: '2',
			comments_count: '0',
			is_liked_by_viewer: false,
		},
		jlpt_levels: { n1: 2, n2: 8, n3: 21, n4: 7, n5: 4, uncommon: 0 },
		items: [],
		created_at: '2026-04-01T12:00:00+00:00',
		updated_at: '2026-04-02T12:00:00+00:00',
		...overrides,
	}) as CatalogueDetailResource;

/** A sentence the signed-in member wrote (an imported one has no author and cannot be edited). */
export const ownSentence = (overrides: Partial<SentenceResource> = {}): SentenceResource => ({
	...sentenceShort,
	uuid: SENTENCE_UUID,
	user_id: users.member.id,
	tatoeba_entry: null,
	content: '親友は何人いるの？',
	...overrides,
});

/** Throws the 422 the form requests send for a rejected field, in the `{ message, errors }` shape. */
export const rejectWith = (errors: Record<string, string[]>): never => {
	throw new Response(JSON.stringify({ message: 'The given data was invalid.', errors }), {
		status: 422,
		headers: { 'Content-Type': 'application/json' },
	});
};

/** Throws an empty response with `status`, which the generated client reports as an HTTP error. */
export const failWith = (status: 403 | 404 | 500): never => {
	throw new Response(null, { status });
};
