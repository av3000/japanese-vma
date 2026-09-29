import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import linkStyles from '../Link/Link.module.css';
import { Button } from './';
import buttonStyles from './Button.module.css';

const render = (element: React.ReactElement) => renderToStaticMarkup(<MemoryRouter>{element}</MemoryRouter>);

describe('Button rendered as a router link', () => {
	it('navigates to `to` as a real anchor', () => {
		const html = render(<Button to="/register">Sign up</Button>);

		expect(html).toMatch(/^<a [^>]*href="\/register"[^>]*>Sign up<\/a>$/);
	});

	it('keeps the button variant classes', () => {
		const html = render(
			<Button to="/register" variant="primary" size="sm">
				Sign up
			</Button>,
		);

		expect(html).toContain(buttonStyles.button);
		expect(html).toContain(buttonStyles.variantPrimary);
		expect(html).toContain(buttonStyles.sizeSm);
	});

	it("does not pick up Link's own classes, whose text colour would override the variant's", () => {
		const html = render(
			<Button to="/register" variant="primary">
				Sign up
			</Button>,
		);

		expect(html).not.toContain(linkStyles.colorDefault);
		expect(html).not.toContain(linkStyles.link);
	});

	it('still accepts the legacy `route` prop', () => {
		const html = render(<Button route={{ externalRoute: '/kanjis' }}>Kanji</Button>);

		expect(html).toContain('href="/kanjis"');
	});
});
