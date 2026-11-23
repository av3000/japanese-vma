import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import type { MappedPostListItem } from '@/api/posts/reads';
import { postTopicLabel } from '../PostCues';
import { communityPosts, makePostListItem } from '../fixtures';
import { PostCard } from './';

const render = (post: MappedPostListItem) =>
	renderToStaticMarkup(
		<MemoryRouter>
			<PostCard post={post} />
		</MemoryRouter>,
	);

describe('PostCard', () => {
	it('is one link to the post, named by its title', () => {
		const html = render(communityPosts.default);

		expect(html.match(/<a /g)).toHaveLength(1);
		const link = html.match(/<h2[^>]*>(<a [^>]*>)(.*?)<\/a><\/h2>/);
		expect(link?.[1]).toContain('href="/community/post-41"');
		expect(link?.[2]).toBe('How do you remember the difference between は and が?');
	});

	it('marks a Japanese title as Japanese, and only then', () => {
		expect(render(communityPosts.japanese)).toMatch(/<a [^>]*lang="ja"/);
		expect(render(communityPosts.default)).not.toMatch(/<a [^>]*lang="ja"/);
	});

	it('names the topic in text, with a "Topic:" prefix for screen readers', () => {
		const html = render(communityPosts.default);

		expect(html).toMatch(/<span[^>]*>Topic: <\/span>FAQ<\/span>/);
	});

	it('shows the Locked pill only for a locked post', () => {
		expect(render(communityPosts.locked)).toContain('>Locked</span>');
		expect(render(communityPosts.locked)).toContain('data-icon="lockSolid"');
		expect(render(communityPosts.default)).not.toContain('Locked');
	});

	it('shows the author and a machine-readable date', () => {
		const html = render(communityPosts.default);

		expect(html).toContain('>by Hanako Sato</span>');
		expect(html).toContain('dateTime="2026-09-28T09:30:00Z"');
	});

	it('shows three tags, then "+N"', () => {
		const html = render(communityPosts.longest);

		expect(html.match(/<li[^>]*title="/g)).toHaveLength(3);
		expect(html).toContain('>+2</span>');
	});

	it('reads views, comments and likes, and counts missing engagement as 0', () => {
		const html = render(communityPosts.longest);

		expect(html.match(/data-stat="(\w+)"/g)).toEqual([
			'data-stat="views"',
			'data-stat="comments"',
			'data-stat="likes"',
		]);
		expect(html).toMatch(/9,999,999<span[^>]*> views<\/span>/);
		expect(render(communityPosts.empty).match(/>0<span/g)).toHaveLength(3);
	});
});

describe('postTopicLabel', () => {
	it('prefers the label the backend sends', () => {
		expect(postTopicLabel(makePostListItem({ topic: 3, topic_label: 'FAQ' }))).toBe('FAQ');
	});

	it('falls back to the local vocabulary, then to "Other"', () => {
		expect(postTopicLabel({ topic: 5, topic_label: '' })).toBe('Bug');
		expect(postTopicLabel({ topic: 99, topic_label: '' })).toBe('Other');
	});
});
