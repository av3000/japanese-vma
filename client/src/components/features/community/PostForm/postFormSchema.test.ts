import { describe, expect, it } from 'vitest';
import {
	buildPostCreatePayload,
	buildPostUpdatePayload,
	MAX_CONTENT_LENGTH,
	MAX_TAG_LENGTH,
	MAX_TAG_QUANTITY,
	MAX_TITLE_LENGTH,
	postFormSchema,
	type PostFormValues,
} from './postFormSchema';

const values = (overrides: Partial<PostFormValues> = {}): PostFormValues => ({
	title: 'How do I read this kanji?',
	content: 'The second character keeps throwing me.',
	topic: 1,
	tags: [],
	...overrides,
});

const messagesFor = (input: PostFormValues) => {
	const result = postFormSchema.safeParse(input);

	return result.success ? [] : result.error.issues.map((issue) => issue.message);
};

describe('postFormSchema', () => {
	it('accepts a value inside every StorePostRequest bound', () => {
		expect(postFormSchema.safeParse(values({ tags: ['howto', 'kanji'] })).success).toBe(true);
	});

	it('measures trimmed length, because the server trims before it validates', () => {
		// 4 characters of content padded to 6 would pass an untrimmed min:5 check and then 422.
		expect(messagesFor(values({ content: '  abcd  ' }))).toContain(
			'Content must be at least 5 characters.',
		);
		expect(messagesFor(values({ title: '  a  ' }))).toContain('Title must be at least 2 characters.');
	});

	it('rejects an empty title or content', () => {
		expect(messagesFor(values({ title: '   ' }))).toContain('Title is required.');
		expect(messagesFor(values({ content: '' }))).toContain('Content is required.');
	});

	it('rejects values past the contract maximums', () => {
		expect(messagesFor(values({ title: 'a'.repeat(MAX_TITLE_LENGTH + 1) }))).toContain(
			`Title must be at most ${MAX_TITLE_LENGTH} characters.`,
		);
		expect(messagesFor(values({ content: 'a'.repeat(MAX_CONTENT_LENGTH + 1) }))).toContain(
			`Content must be at most ${MAX_CONTENT_LENGTH} characters.`,
		);
	});

	it('rejects a topic outside the canonical vocabulary', () => {
		expect(messagesFor(values({ topic: 8 as PostFormValues['topic'] }))).toContain(
			'Topic must be one of the supported post topics.',
		);
		expect(messagesFor(values({ topic: 7 }))).toEqual([]);
	});

	it('mirrors the tags rules the server enforces', () => {
		expect(messagesFor(values({ tags: Array.from({ length: MAX_TAG_QUANTITY + 1 }, (_, i) => `t${i}`) }))).toContain(
			`Maximum ${MAX_TAG_QUANTITY} tags allowed.`,
		);
		expect(messagesFor(values({ tags: ['a'.repeat(MAX_TAG_LENGTH + 1)] }))).toContain(
			`Each tag must be at most ${MAX_TAG_LENGTH} characters.`,
		);
		expect(messagesFor(values({ tags: ['howto', 'howto'] }))).toContain('Duplicate tags are not allowed.');
		expect(messagesFor(values({ tags: ['  '] }))).toContain('Tags cannot be empty.');
	});
});

describe('buildPostCreatePayload', () => {
	it('sends the trimmed StorePostRequest shape', () => {
		expect(
			buildPostCreatePayload(values({ title: '  Title  ', content: '  Body text  ', tags: [' howto '] })),
		).toEqual({
			title: 'Title',
			content: 'Body text',
			topic: 1,
			tags: ['howto'],
		});
	});
});

describe('buildPostUpdatePayload', () => {
	it('sends only the fields the author touched, so a concurrent edit is not overwritten', () => {
		expect(buildPostUpdatePayload(values({ title: 'New title' }), ['title'])).toEqual({ title: 'New title' });
	});

	it('sends an empty tags array as a real value, which is how tags are cleared', () => {
		expect(buildPostUpdatePayload(values({ tags: [] }), ['tags'])).toEqual({ tags: [] });
	});

	it('produces an empty body when nothing is dirty, which the route refuses to send', () => {
		expect(buildPostUpdatePayload(values(), [])).toEqual({});
	});
});
