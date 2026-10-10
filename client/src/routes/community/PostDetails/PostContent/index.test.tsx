import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { PostDetailResource } from '@/api/generated/model';
import { makeHashtags } from '@/components/features/Homepage/fixtures';
import { LONGEST_POST_TITLE, makePostDetail } from '@/components/features/community/fixtures';
import PostContent, { postTagHref } from './index';

const commentsBlockProps: Array<Record<string, unknown>> = [];

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');

	return {
		...actual,
		Link: ({ children, to, className }: { children: ReactNode; to: string; className?: string }) => (
			<a href={to} className={className}>
				{children}
			</a>
		),
	};
});

vi.mock('@/components/features/comment/CommentsBlock', () => ({
	default: (props: Record<string, unknown>) => {
		commentsBlockProps.push(props);
		return <div>Comment thread</div>;
	},
}));

// The mutating controls carry their own query and auth wiring; this file is a read
// surface, so they are stubbed rather than exercised here.
vi.mock('@/components/features/community/PostLikeButton', () => ({
	default: () => <button type="button">Like stub</button>,
}));
vi.mock('@/components/features/community/PostOwnerActions', () => ({
	default: () => <div>Owner actions stub</div>,
}));

const render = (overrides: Partial<PostDetailResource> = {}) =>
	renderToStaticMarkup(<PostContent post={makePostDetail(overrides)} />);

describe('PostContent', () => {
	beforeEach(() => {
		commentsBlockProps.length = 0;
	});

	// The detail route is addressed by uuid while the create transport needs the
	// numeric id, so both have to reach the seam and neither may stand in for the other.
	it('composes comments through the shared seam on the post parent, under an h2', () => {
		const html = render();

		expect(commentsBlockProps).toEqual([{ parent: 'post', entityId: 41, entityUuid: 'post-41', isLocked: false }]);
		expect(html).toMatch(/<h2[^>]*>Comments<\/h2>/);
	});

	it('shows the Locked cue and forwards the lock so the composer and replies are withheld', () => {
		const html = render({ locked: true });

		expect(commentsBlockProps[0]).toMatchObject({ isLocked: true });
		expect(html).toContain('>Locked</span>');
		// A locked post still renders the thread; only the write affordances close.
		expect(html).toContain('Comment thread');
	});

	it('heads the page with the back link, topic, one h1 and the byline', () => {
		const html = render();

		expect(html).toMatch(/<a href="\/community"[^>]*>.*Community<\/a>/);
		expect(html).toMatch(/<span[^>]*>Topic: <\/span>FAQ<\/span>/);
		expect(html.match(/<h1[\s>]/g)).toHaveLength(1);
		expect(html).toContain('>Hanako Sato</strong>');
		expect(html).toContain('348 views');
		expect(html).not.toContain('Locked');
	});

	it('marks a Japanese title as Japanese, and keeps the longest title whole', () => {
		expect(render({ title: '日本語の勉強方法' })).toMatch(/<h1[^>]*lang="ja"/);
		expect(render({ title: LONGEST_POST_TITLE })).toContain(`>${LONGEST_POST_TITLE}</h1>`);
	});

	it('splits the body into paragraphs at its line breaks, as text', () => {
		const html = render({ content: 'First line.\n\nSecond <b>line</b>.\r\nThird.' });

		expect(html.match(/<p class="[^"]*paragraph[^"]*">/g)).toHaveLength(3);
		expect(html).toContain('Second &lt;b&gt;line&lt;/b&gt;.');
	});

	it('names the rail and lists likes, comments and views', () => {
		const html = render();

		expect(html).toContain('aria-label="About this post"');
		expect(html).toMatch(/<dt[^>]*>Likes<\/dt><dd[^>]*>12<\/dd>/);
		expect(html).toMatch(/<dt[^>]*>Comments<\/dt><dd[^>]*>9<\/dd>/);
		expect(html).toMatch(/<dt[^>]*>Views<\/dt><dd[^>]*>348<\/dd>/);
		expect(html).toContain('Like stub');
		expect(html).toContain('Owner actions stub');
	});

	it('links each tag to the list filtered by it, and hides Tags when there are none', () => {
		const html = render({ hashtags: makeHashtags(['grammar', '#particles']) });

		expect(html).toContain('href="/community?hashtag=grammar"');
		expect(html).toContain('href="/community?hashtag=particles"');
		expect(html).toContain('>#particles</a>');
		expect(render({ hashtags: [] })).not.toMatch(/>Tags<\/h2>/);
	});
});

describe('postTagHref', () => {
	it('encodes the tag', () => {
		expect(postTagHref('日本語')).toBe('/community?hashtag=%E6%97%A5%E6%9C%AC%E8%AA%9E');
	});
});
