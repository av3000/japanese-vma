import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { Button } from '@/components/shared/Button';
import { DetailActionGroup, DetailActions } from './';

describe('DetailActions', () => {
	it("keeps each Button's own pressed, expanded and loading state", () => {
		const html = renderToStaticMarkup(
			<DetailActions>
				<Button variant="outline" isFullWidth aria-pressed={true}>
					Like · 3
				</Button>
				<Button variant="outline" isFullWidth aria-controls="article-pdf-modal" aria-expanded={false}>
					Kanji &amp; words PDF
				</Button>
			</DetailActions>,
		);

		expect(html).toContain('aria-pressed="true"');
		expect(html).toContain('aria-controls="article-pdf-modal"');
		expect(html).toContain('aria-expanded="false"');
	});

	it('names a group by its heading and shows its meta before the actions', () => {
		const html = renderToStaticMarkup(
			<DetailActionGroup heading="Moderation" meta={<span>Pending</span>}>
				<Button variant="outline">Review</Button>
			</DetailActionGroup>,
		);
		const headingId = html.match(/<h2 id="([^"]+)"/)?.[1];

		expect(headingId).toBeTruthy();
		expect(html).toMatch(new RegExp(`<section[^>]*aria-labelledby="${headingId}"`));
		expect(html.indexOf('Pending')).toBeLessThan(html.indexOf('Review'));
	});

	it('renders a single action and a group of five', () => {
		const html = renderToStaticMarkup(
			<DetailActionGroup heading="Your article" headingLevel={3}>
				{['One', 'Two', 'Three', 'Four', 'Five'].map((label) => (
					<Button key={label} variant="outline">
						{label}
					</Button>
				))}
			</DetailActionGroup>,
		);

		expect(html).toContain('<h3');
		expect(html.match(/<button/g)).toHaveLength(5);
	});
});
