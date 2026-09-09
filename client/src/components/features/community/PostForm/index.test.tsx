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
	submitLabel: 'Create Post',
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
		expect(html).toContain('25/255');
		expect(html).toContain('39/15000');
		// The tags control renders its value directly.
		expect(html).toContain('howto');
	});

	it('renders the topic the Post already carries as the selected option', () => {
		const html = renderToStaticMarkup(<PostForm {...baseProps} />);

		expect(html).toContain('value="5"');
	});

	it('disables submit while a write is in flight, so a second submit cannot start', () => {
		const html = renderToStaticMarkup(<PostForm {...baseProps} isSubmitting />);

		expect(html).toContain('disabled');
		expect(html).not.toContain('>Create Post<');
	});

	it('disables submit on a pristine edit form, which would otherwise send an empty update body', () => {
		const html = renderToStaticMarkup(<PostForm {...baseProps} submitLabel="Update Post" disableSubmitWhenUnchanged />);

		expect(html).toContain('disabled');
	});

	it('renders a status message from a rejected write', () => {
		const html = renderToStaticMarkup(<PostForm {...baseProps} statusMessage="This post is not yours." />);

		expect(html).toContain('This post is not yours.');
	});
});
