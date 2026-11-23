import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { AuthCard } from './';

const form = <form aria-label="Log in form" />;

describe('AuthCard', () => {
	it('renders the title as the only h1 and names the section with it', () => {
		const html = renderToStaticMarkup(<AuthCard title="Welcome back">{form}</AuthCard>);

		expect(html.match(/<h1[ >]/g)).toHaveLength(1);

		const id = html.match(/<h1 id="([^"]+)"/)?.[1];
		expect(id).toBeTruthy();
		expect(html).toMatch(new RegExp(`<section[^>]*aria-labelledby="${id}"`));
		expect(html).toMatch(/<h1[^>]*>Welcome back<\/h1>/);
	});

	it('renders the alert before the form and the footer after it', () => {
		const html = renderToStaticMarkup(
			<AuthCard
				title="Welcome back"
				alert={<div role="alert">Wrong password</div>}
				footer={<a href="/register">Create an account</a>}
			>
				{form}
			</AuthCard>,
		);

		const alertAt = html.indexOf('Wrong password');
		const formAt = html.indexOf('<form');
		const footerAt = html.indexOf('Create an account');

		expect(alertAt).toBeGreaterThan(html.indexOf('</h1>'));
		expect(formAt).toBeGreaterThan(alertAt);
		expect(footerAt).toBeGreaterThan(formAt);
		expect(html).toMatch(/<footer[^>]*><a href="\/register">Create an account<\/a><\/footer>/);
	});

	it('renders no intro, alert or footer wrappers when those slots are empty', () => {
		const html = renderToStaticMarkup(<AuthCard title="Welcome back">{form}</AuthCard>);

		expect(html).not.toContain('<p');
		expect(html).not.toContain('<footer');
		expect(html).not.toContain('role="alert"');
	});

	it('renders the intro as one paragraph under the title', () => {
		const html = renderToStaticMarkup(
			<AuthCard title="Welcome back" intro="Log in to keep your reading lists in sync.">
				{form}
			</AuthCard>,
		);

		expect(html).toMatch(/<\/h1><p[^>]*>Log in to keep your reading lists in sync\.<\/p>/);
	});
});
