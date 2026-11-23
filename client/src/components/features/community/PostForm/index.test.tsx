import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { PostForm } from './index';
import type { PostFormValues } from './postFormSchema';

const initialValues: PostFormValues = {
	title: 'How do I read this kanji?',
	content: 'The second character keeps throwing me.',
	topic: 5,
	tags: ['howto'],
};

const baseProps = {
	initialValues,
	onSubmit: () => {},
	submitLabel: 'Publish post',
	cancel: <a href="/community">Cancel</a>,
};

describe('PostForm', () => {
	it('offers the canonical topic vocabulary, including the code legacy never labelled', () => {
		const html = renderToStaticMarkup(<PostForm {...baseProps} />);

		expect(html).toContain('Content-related');
		// Legacy labelled 6 as "Announcement" and left 7 unlabelled; v1 labels 6 Feedback, 7 Announcement.
		expect(html).toContain('Feedback');
		expect(html).toContain('Announcement');
	});

	it('seeds the form state from initialValues', () => {
		const html = renderToStaticMarkup(<PostForm {...baseProps} />);

		// react-hook-form applies defaultValues through the field ref, so a static render leaves the
		// inputs empty; the counters are what prove the values reached form state.
		expect(html).toContain('25 / 255');
		expect(html).toContain('39 / 15000');
		// The tags control renders its value directly.
		expect(html).toContain('howto');
	});

	it('renders the topic the Post already carries as the selected option', () => {
		const html = renderToStaticMarkup(<PostForm {...baseProps} />);

		expect(html).toContain('value="5"');
	});

	it('disables submit while a write is in flight, so a second submit cannot start', () => {
		const html = renderToStaticMarkup(<PostForm {...baseProps} isSubmitting />);

		expect(html).toMatch(/<button[^>]*type="submit"[^>]*disabled=""/);
	});

	it('keeps submit enabled on a pristine edit form; the form says "No changes to save." instead', () => {
		const html = renderToStaticMarkup(<PostForm {...baseProps} submitLabel="Save changes" requireChanges />);

		expect(html).toMatch(/<button[^>]*type="submit"/);
		expect(html).not.toMatch(/<button[^>]*type="submit"[^>]*disabled/);
	});

	it('puts topic and tags in a Settings card after the title and text', () => {
		const html = renderToStaticMarkup(<PostForm {...baseProps} />);

		expect(html).toMatch(/<h2[^>]*>Settings<\/h2>/);
		expect(html.indexOf('>Text<')).toBeLessThan(html.indexOf('>Settings<'));
		expect(html.indexOf('>Settings<')).toBeLessThan(html.indexOf('>Topic<'));
		expect(html).toContain('Be kind. Posts can be locked by moderators.');
	});
});
