import React from 'react';
import { Link } from 'react-router-dom';
import { Container } from '@/components/shared/layout';
import { NAV_SECTIONS } from '@/shared/constants/navigation';
import styles from './Footer.module.css';

const CONTACT_EMAIL = 'jplearning.online@gmail.com';

/**
 * The site footer, derived from the flat Header: the same white surface and hairline, the brand
 * on the left, the Explore and Dictionary groups as link columns, then the dictionary licences.
 *
 * The attribution paragraphs are a licence condition of the data this site is built on (JMdict,
 * Kanjidic2, JMnedict, Radkfile, Tatoeba, the JLPT lists). Keep their wording and links intact.
 */
const Footer: React.FC = () => {
	const currentYear = new Date().getFullYear();

	return (
		<footer className={styles.footer}>
			<Container>
				<div className={styles.top}>
					<div className={styles.brandBlock}>
						<Link to="/" className={styles.brand}>
							JPLearning
						</Link>
						<p className={styles.tagline}>Graded Japanese reading, with the dictionary one tap away.</p>
					</div>

					<nav aria-label="Footer" className={styles.nav}>
						{NAV_SECTIONS.map((section) => (
							<div key={section.id}>
								<h2 className={styles.groupLabel}>{section.label}</h2>
								<ul className={styles.links}>
									{section.links.map((link) => (
										<li key={link.to}>
											<Link to={link.to} className={styles.link}>
												{link.label}
											</Link>
										</li>
									))}
								</ul>
							</div>
						))}
					</nav>
				</div>

				<div className={styles.attribution}>
					<p>
						This site uses the{' '}
						<a href="http://www.edrdg.org/wiki/index.php/JMdict-EDICT_Dictionary_Project">JMdict</a>,{' '}
						<a href="http://www.edrdg.org/wiki/index.php/KANJIDIC_Project">Kanjidic2</a>,{' '}
						<a href="http://www.edrdg.org/enamdict/enamdict_doc.html">JMnedict</a>, and{' '}
						<a href="http://www.edrdg.org/krad/kradinf.html">Radkfile</a> dictionary files. These files are
						the property of the Electronic Dictionary Research and Development{' '}
						<a href="http://www.edrdg.org/">Group</a>, and are used in conformance with the Group&apos;s{' '}
						<a href="http://www.edrdg.org/edrdg/licence.html">licence</a>.
					</p>
					<p>
						Example sentences come from the <a href="http://tatoeba.org/">Tatoeba</a> project and are
						licensed under{' '}
						<a href="http://creativecommons.org/licenses/by/2.0/fr/">Creative Common CC-BY</a>. JLPT data
						comes from Jonathan Waller&apos;s <a href="http://www.tanos.co.uk/jlpt/">JLPT Resources</a>{' '}
						page.
					</p>
				</div>

				<p className={styles.legal}>
					&copy; {currentYear} JPLearning &middot; <a href={`mailto:${CONTACT_EMAIL}`}>{CONTACT_EMAIL}</a>
				</p>
			</Container>
		</footer>
	);
};

export default Footer;
