import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { CardCover, CoverChip } from './';

describe('CardCover', () => {
	it('hides only the decorative glyph from assistive technology', () => {
		const html = renderToStaticMarkup(
			<CardCover
				glyph="字"
				badge={<CoverChip>Kanji</CoverChip>}
				caption="128 items"
				status={<span>Processing</span>}
			/>,
		);

		expect(html).toMatch(/<span[^>]*lang="ja"[^>]*aria-hidden="true"[^>]*>字<\/span>/);
		expect(html.match(/aria-hidden="true"/g)).toHaveLength(1);
		expect(html).toContain('>Kanji</span>');
		expect(html).toContain('>128 items</span>');
		expect(html).toContain('>Processing</span>');
	});

	it('renders no empty slots', () => {
		const html = renderToStaticMarkup(<CardCover glyph="記" />);

		expect(html.match(/<span/g)).toHaveLength(1);
	});
});
