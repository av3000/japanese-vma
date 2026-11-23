import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { FormField, Input, Textarea } from './';

const describedBy = (html: string) => html.match(/<input[^>]*aria-describedby="([^"]*)"/)?.[1];

describe('FormField', () => {
	it('points the label at the control with a generated id', () => {
		const html = renderToStaticMarkup(<FormField label="Email">{(control) => <Input {...control} />}</FormField>);

		const id = html.match(/<input[^>]*id="([^"]+)"/)?.[1];
		expect(id).toBeTruthy();
		expect(html).toContain(`for="${id}"`);
	});

	it('respects a custom id', () => {
		const html = renderToStaticMarkup(
			<FormField label="Email" id="email">
				{(control) => <Input {...control} />}
			</FormField>,
		);

		expect(html).toContain('for="email"');
		expect(html).toMatch(/<input[^>]*id="email"/);
	});

	it('describes the control by its hint alone', () => {
		const html = renderToStaticMarkup(
			<FormField label="Username" id="name" hint="Letters, numbers, underscores and hyphens.">
				{(control) => <Input {...control} />}
			</FormField>,
		);

		expect(describedBy(html)).toBe('name-hint');
		expect(html).toMatch(/<p [^>]*id="name-hint"[^>]*>Letters, numbers, underscores and hyphens\.<\/p>/);
		expect(html).not.toContain('aria-invalid');
	});

	it('describes the control by its error alone and marks it invalid', () => {
		const html = renderToStaticMarkup(
			<FormField label="Email" id="email" error="The email has already been taken.">
				{(control) => <Input {...control} />}
			</FormField>,
		);

		expect(describedBy(html)).toBe('email-error');
		expect(html).toMatch(/<div id="email-error"><p[^>]*>The email has already been taken\.<\/p><\/div>/);
		expect(html).toMatch(/<input[^>]*aria-invalid="true"/);
	});

	it('lists the hint before the error when both render', () => {
		const html = renderToStaticMarkup(
			<FormField label="Password" id="password" hint="At least 8 characters." error="Too short.">
				{(control) => <Input {...control} />}
			</FormField>,
		);

		expect(describedBy(html)).toBe('password-hint password-error');
		expect(html.indexOf('password-hint"')).toBeLessThan(html.indexOf('password-error"><p'));
	});

	it('omits aria-describedby and aria-invalid when there is neither hint nor error', () => {
		const html = renderToStaticMarkup(
			<FormField label="Email" id="email">
				{(control) => <Input {...control} />}
			</FormField>,
		);

		expect(html).not.toContain('aria-describedby');
		expect(html).not.toContain('aria-invalid');
	});

	it('renders every message of an error array on its own line', () => {
		const html = renderToStaticMarkup(
			<FormField label="Password" id="password" error={['Too short.', 'Needs a number.', 'Needs a symbol.']}>
				{(control) => <Input {...control} />}
			</FormField>,
		);

		expect(html.match(/<div id="password-error">(.*?)<\/div>/)?.[1].match(/<p[ >]/g)).toHaveLength(3);
	});

	it('treats an empty string or empty array as no error', () => {
		const empty = renderToStaticMarkup(
			<FormField label="Email" id="email" error="">
				{(control) => <Input {...control} />}
			</FormField>,
		);
		const none = renderToStaticMarkup(
			<FormField label="Email" id="email" error={[]}>
				{(control) => <Input {...control} />}
			</FormField>,
		);

		for (const html of [empty, none]) {
			expect(html).not.toContain('email-error');
			expect(html).not.toContain('aria-invalid');
		}
	});

	it('wires any control, not only Input', () => {
		const html = renderToStaticMarkup(
			<FormField label="Bio" id="bio" error="Too long.">
				{(control) => <Textarea {...control} />}
			</FormField>,
		);

		const textarea = html.match(/<textarea[^>]*>/)?.[0] ?? '';

		expect(textarea).toContain('id="bio"');
		expect(textarea).toContain('aria-invalid="true"');
		expect(textarea).toContain('aria-describedby="bio-error"');
	});
	it('renders the counter on the hint row but leaves it out of aria-describedby', () => {
		const html = renderToStaticMarkup(
			<FormField label="Title" id="title" hint="Up to 255 characters." counter="12 / 255">
				{(control) => <Input {...control} />}
			</FormField>,
		);

		expect(describedBy(html)).toBe('title-hint');
		expect(html).toContain('12 / 255');
		expect(html.indexOf('title-hint"')).toBeLessThan(html.indexOf('12 / 255'));
		expect(html).not.toMatch(/aria-live/);
	});
});
