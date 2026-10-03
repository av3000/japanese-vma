import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { SentenceForm } from './index';
import { buildSentenceWritePayload, sentenceFormSchema } from './sentenceFormSchema';

const parse = (content: string) => sentenceFormSchema.safeParse({ content });

describe('sentenceFormSchema', () => {
	it('accepts the boundaries the backend accepts', () => {
		expect(parse('あいうえ').success).toBe(true);
		expect(parse('あ'.repeat(300)).success).toBe(true);
	});

	it('rejects content outside the 4-300 range', () => {
		expect(parse('あいう').success).toBe(false);
		expect(parse('あ'.repeat(301)).success).toBe(false);
		expect(parse('').success).toBe(false);
	});

	it('measures after trimming, the way prepareForValidation does on the server', () => {
		expect(parse('   abc   ').success).toBe(false);
		expect(parse('  abcd  ').success).toBe(true);
	});
});

describe('buildSentenceWritePayload', () => {
	it('trims before sending', () => {
		expect(buildSentenceWritePayload({ content: '  水を飲みます。  ' })).toEqual({ content: '水を飲みます。' });
	});
});

describe('SentenceForm', () => {
	const baseProps = {
		initialValues: { content: '水を飲みます。' },
		onSubmit: () => {},
		submitLabel: 'Create sentence',
		cancel: <a href="/sentences">Cancel</a>,
	};

	it('seeds the form from initialValues and renders the submit label', () => {
		const html = renderToStaticMarkup(<SentenceForm {...baseProps} />);

		// react-hook-form applies defaultValues through the field ref, so a static
		// render leaves the textarea empty; the counter is what proves the value
		// reached the form state.
		expect(html).toContain('7 / 300');
		expect(html).toContain('Create sentence');
	});

	it('labels the field and describes it by its length rule', () => {
		const html = renderToStaticMarkup(<SentenceForm {...baseProps} />);

		const id = html.match(/<textarea[^>]*id="([^"]+)"/)?.[1];
		expect(html).toContain(`for="${id}"`);
		expect(html).toMatch(/<label[^>]*>Sentence<\/label>/);
		expect(html).toMatch(new RegExp(`<textarea[^>]*aria-describedby="${id}-hint"`));
		expect(html).toContain('Between 4 and 300 characters.');
	});

	it('disables submit while a write is in flight, so a second submit cannot start', () => {
		const html = renderToStaticMarkup(<SentenceForm {...baseProps} isSubmitting />);

		expect(html).toMatch(/<button[^>]*type="submit"[^>]*disabled=""/);
	});
});
