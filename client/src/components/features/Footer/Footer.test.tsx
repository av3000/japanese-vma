import { renderToStaticMarkup } from 'react-dom/server';
import { MemoryRouter } from 'react-router-dom';
import { describe, expect, it } from 'vitest';
import { DICTIONARY_LINKS, EXPLORE_LINKS } from '@/shared/constants/navigation';
import Footer from './index';

const render = () =>
	renderToStaticMarkup(
		<MemoryRouter>
			<Footer />
		</MemoryRouter>,
	);

/** Every link the licence text must keep; see the Footer's doc comment. */
const LICENCE_LINKS = [
	'http://www.edrdg.org/wiki/index.php/JMdict-EDICT_Dictionary_Project',
	'http://www.edrdg.org/wiki/index.php/KANJIDIC_Project',
	'http://www.edrdg.org/enamdict/enamdict_doc.html',
	'http://www.edrdg.org/krad/kradinf.html',
	'http://www.edrdg.org/',
	'http://www.edrdg.org/edrdg/licence.html',
	'http://tatoeba.org/',
	'http://creativecommons.org/licenses/by/2.0/fr/',
	'http://www.tanos.co.uk/jlpt/',
];

describe('Footer', () => {
	it('keeps the dictionary licence attribution intact', () => {
		const html = render();

		for (const href of LICENCE_LINKS) {
			expect(html).toContain(`href="${href}"`);
		}
		expect(html).toContain('JMdict');
		expect(html).toContain('Kanjidic2');
		expect(html).toContain('JMnedict');
		expect(html).toContain('Radkfile');
		expect(html).toContain('Electronic Dictionary Research and Development');
		expect(html).toContain('Tatoeba');
		expect(html).toContain('Creative Common CC-BY');
		expect(html).toContain('JLPT Resources');
	});

	it('renders the same Explore and Dictionary links as the Header, in order', () => {
		const html = render();
		const nav = html.slice(html.indexOf('<nav'), html.indexOf('</nav>'));

		expect(nav).toContain('aria-label="Footer"');
		const hrefs = [...nav.matchAll(/href="([^"]+)"/g)].map((match) => match[1]);
		expect(hrefs).toEqual([...EXPLORE_LINKS, ...DICTIONARY_LINKS].map((link) => link.to));
		expect(nav).toContain('>Explore<');
		expect(nav).toContain('>Dictionary<');
	});

	it('links the brand home and the contact address, with the current year', () => {
		const html = render();

		expect(html).toMatch(/<a[^>]*href="\/"[^>]*>JPLearning<\/a>/);
		expect(html).toContain('href="mailto:jplearning.online@gmail.com"');
		expect(html).toContain(`© ${new Date().getFullYear()} JPLearning`);
	});

	it('has no placeholder social links or legal labels that never linked anywhere', () => {
		const html = render();

		expect(html).not.toContain('facebook.com');
		expect(html).not.toContain('instagram.com');
		expect(html).not.toContain('<img');
		expect(html).not.toContain('Terms and Conditions');
		expect(html).not.toContain('Privacy policy');
	});
});
