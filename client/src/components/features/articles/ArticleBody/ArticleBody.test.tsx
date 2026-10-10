import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { ArticleBody, ArticleTitle, toParagraphs } from './';

describe('toParagraphs', () => {
	it('splits on every kind of line break and drops blank lines', () => {
		expect(toParagraphs('一段落目。\n\n二段落目。\r\n三段落目。\r四段落目。\n  \n')).toEqual([
			'一段落目。',
			'二段落目。',
			'三段落目。',
			'四段落目。',
		]);
	});

	it('keeps a leading ideographic space, the Japanese paragraph indent', () => {
		expect(toParagraphs('　字下げ。 \t')).toEqual(['　字下げ。']);
	});

	it('returns nothing for empty text', () => {
		expect(toParagraphs('')).toEqual([]);
		expect(toParagraphs(null)).toEqual([]);
		expect(toParagraphs(' \n \n')).toEqual([]);
	});
});

describe('ArticleBody', () => {
	it('renders one Japanese paragraph per line', () => {
		const html = renderToStaticMarkup(<ArticleBody contentJp={'一。\n\n二。\n三。'} />);

		expect(html.match(/<p[ >]/g)).toHaveLength(3);
		expect(html).toMatch(/<div[^>]*lang="ja"[^>]*><p[^>]*>一。<\/p>/);
	});

	it('renders markup in the text as text', () => {
		const html = renderToStaticMarkup(<ArticleBody contentJp={'<b>太字</b>'} />);

		expect(html).toContain('&lt;b&gt;太字&lt;/b&gt;');
		expect(html).not.toContain('<b>');
	});

	it('puts the translation in a closed disclosure marked English', () => {
		const html = renderToStaticMarkup(<ArticleBody contentJp="本文。" contentEn={'First.\nSecond.'} />);

		expect(html).toMatch(/<details[^>]*><summary[^>]*>English translation<\/summary>/);
		expect(html).not.toMatch(/<details[^>]*open/);
		expect(html).toMatch(/<div[^>]*lang="en"[^>]*><p[^>]*>First\.<\/p><p[^>]*>Second\.<\/p>/);
	});

	it('leaves the translation out when it is empty', () => {
		expect(renderToStaticMarkup(<ArticleBody contentJp="本文。" contentEn={'  \n'} />)).not.toContain('<details');
		expect(renderToStaticMarkup(<ArticleBody contentJp="本文。" contentEn={null} />)).not.toContain('<details');
	});

	it('places the attribution between the text and the translation', () => {
		const html = renderToStaticMarkup(
			<ArticleBody contentJp="本文。" contentEn="Text." attribution={<aside>Credit</aside>} />,
		);

		expect(html.indexOf('本文。')).toBeLessThan(html.indexOf('Credit'));
		expect(html.indexOf('Credit')).toBeLessThan(html.indexOf('<details'));
	});
});

describe('ArticleTitle', () => {
	it('renders the Japanese title as the h1 and the English one as a subtitle', () => {
		const html = renderToStaticMarkup(<ArticleTitle titleJp="ビシバンカの日" titleEn="Vyshyvanka Day" />);

		expect(html).toMatch(/<h1[^>]*lang="ja"[^>]*>ビシバンカの日<\/h1><p[^>]*lang="en"[^>]*>Vyshyvanka Day<\/p>/);
	});

	it('leaves out an empty English title', () => {
		expect(renderToStaticMarkup(<ArticleTitle titleJp="日" titleEn=" " />)).not.toContain('<p');
		expect(renderToStaticMarkup(<ArticleTitle titleJp="日" titleEn={null} />)).not.toContain('<p');
	});
});
