import { describe, expect, it } from 'vitest';
import {
	buildArticleFormSchema,
	MAX_CONTENT_LENGTH,
	MAX_TITLE_LENGTH,
	type ArticleFormValues,
} from './articleFormSchema';

const valid: ArticleFormValues = {
	title_jp: '新宿駅の新しい改札',
	title_en: 'New gates at Shinjuku',
	content_jp: 'ＪＲ新宿駅で、新しい改札が完成しました。',
	content_en: '',
	source_link: 'https://www3.nhk.or.jp/news/easy/k10014.html',
	publicity: true,
	tags: [],
};

const messagesFor = (values: Partial<ArticleFormValues>, field: keyof ArticleFormValues) => {
	const result = buildArticleFormSchema({ requireEnglishTitle: true }).safeParse({ ...valid, ...values });
	return result.success ? [] : result.error.issues.filter((issue) => issue.path[0] === field).map((i) => i.message);
};

describe('buildArticleFormSchema', () => {
	it('accepts a complete article', () => {
		expect(buildArticleFormSchema({ requireEnglishTitle: true }).safeParse(valid).success).toBe(true);
	});

	it('says "required" only for an empty required field', () => {
		expect(messagesFor({ title_jp: '   ' }, 'title_jp')).toEqual(['Japanese title is required.']);
	});

	it('uses the minimum message for a too-short value', () => {
		expect(messagesFor({ title_jp: 'あ' }, 'title_jp')).toEqual(['Japanese title must be at least 2 characters.']);
		expect(messagesFor({ content_jp: '短い' }, 'content_jp')).toEqual([
			'Japanese content must be at least 10 characters.',
		]);
	});

	it('uses the maximum message for a too-long value', () => {
		expect(messagesFor({ title_en: 'x'.repeat(MAX_TITLE_LENGTH + 1) }, 'title_en')).toEqual([
			`English title must be at most ${MAX_TITLE_LENGTH} characters.`,
		]);
		expect(messagesFor({ content_jp: 'あ'.repeat(MAX_CONTENT_LENGTH + 1) }, 'content_jp')).toEqual([
			`Japanese content must be at most ${MAX_CONTENT_LENGTH} characters.`,
		]);
	});
});
