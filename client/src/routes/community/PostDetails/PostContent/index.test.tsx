import type { ReactNode } from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import type { MappedPostDetail } from '@/api/posts/reads';
import PostContent from './index';

const commentsBlockProps: Array<Record<string, unknown>> = [];

vi.mock('react-router-dom', async () => {
	const actual = await vi.importActual<typeof import('react-router-dom')>('react-router-dom');

	return {
		...actual,
		Link: ({ children, to }: { children: ReactNode; to: string }) => <a href={to}>{children}</a>,
	};
});

vi.mock('@/components/features/comment/CommentsBlock', () => ({
	default: (props: Record<string, unknown>) => {
		commentsBlockProps.push(props);
		return <div>Comments</div>;
	},
}));

// The mutating controls carry their own query and auth wiring; this file is a read
// surface, so they are stubbed rather than exercised here.
vi.mock('@/components/features/community/PostLikeButton', () => ({
	default: () => <div>Like</div>,
}));
vi.mock('@/components/features/community/PostOwnerActions', () => ({
	default: () => <div>Owner actions</div>,
}));

const post = {
	id: 12,
	uuid: 'post-uuid',
	title: 'How do I read this kanji?',
	content: 'Body text.',
	locked: false,
	topic_label: 'Kanji',
	hashtags: [],
	author: { id: 5, name: 'Reader' },
	authorName: 'Reader',
	formattedDate: '1/1/2026',
	engagementCounts: { views: 3, likes: 1, comments: 0 },
} as unknown as MappedPostDetail;

const render = (overrides: Partial<MappedPostDetail> = {}) =>
	renderToStaticMarkup(<PostContent post={{ ...post, ...overrides }} />);

describe('PostContent', () => {
	beforeEach(() => {
		commentsBlockProps.length = 0;
	});

	// The detail route is addressed by uuid while the create transport needs the
	// numeric id, so both have to reach the seam and neither may stand in for the other.
	it('composes comments through the shared seam on the post parent', () => {
		render();

		expect(commentsBlockProps).toHaveLength(1);
		expect(commentsBlockProps[0]).toEqual({
			parent: 'post',
			entityId: 12,
			entityUuid: 'post-uuid',
			isLocked: false,
		});
	});

	it('forwards the lock so the composer and reply controls are withheld', () => {
		const html = render({ locked: true });

		expect(commentsBlockProps[0]).toMatchObject({ isLocked: true });
		// A locked post still renders the thread; only the write affordances close.
		expect(html).toContain('Comments');
		expect(html).toContain('Locked');
	});
});
