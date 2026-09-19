import React from 'react';
import { Link } from 'react-router-dom';
import FacebookIcon from '@/assets/icons/fb-icon.svg';
import InstagramIcon from '@/assets/icons/ig-icon.svg';
import { Cluster, Container, Grid, Stack } from '@/components/shared/layout';
import styles from './Footer.module.css';

const currentYear = new Date().getFullYear();

const Footer: React.FC = () => (
	<footer className={styles.footer}>
		<Container>
			<Stack gap="md">
				<div>
					<Link to="/" className={styles.brand}>
						<h4>JPLearning</h4>
					</Link>
				</div>

				<Grid columns={{ base: 1, sm: 2 }} gap="lg">
					<div>
						<p>
							This site uses the{' '}
							<a href="http://www.edrdg.org/wiki/index.php/JMdict-EDICT_Dictionary_Project">JMdict</a>,{' '}
							<a href="http://www.edrdg.org/wiki/index.php/KANJIDIC_Project">Kanjidic2</a>,{' '}
							<a href="http://www.edrdg.org/enamdict/enamdict_doc.html">JMnedict</a>, and{' '}
							<a href="http://www.edrdg.org/krad/kradinf.html">Radkfile</a> dictionary files. These files
							are the property of the Electronic Dictionary Research and Development{' '}
							<a href="http://www.edrdg.org/">Group</a>, and are used in conformance with the Group's{' '}
							<a href="http://www.edrdg.org/edrdg/licence.html">licence</a>.
						</p>
					</div>
					<div>
						<p>
							Example sentences come from the <a href="http://tatoeba.org/">Tatoeba</a> project and are
							licensed under{' '}
							<a href="http://creativecommons.org/licenses/by/2.0/fr/">Creative Common CC-BY</a>.
						</p>
						<p>
							JLPT data comes from Jonathan Waller's JLPT Resources{' '}
							<a href="http://www.tanos.co.uk/jlpt/">page</a>.
						</p>
						<p>
							Contact Us <a href="mailto:jplearning.online@gmail.com">jplearning.online@gmail.com</a> or
							on Socials
						</p>
					</div>
				</Grid>

				<div>
					<Cluster gap="xs" justify="center" className={styles.socialLinks}>
						<Link to="https://www.facebook.com/">
							<img src={FacebookIcon} alt="facebook-social-icon" />
						</Link>
						<Link to="https://www.instagram.com/">
							<img src={InstagramIcon} alt="instagram-social-icon" />
						</Link>
					</Cluster>
					<p className={styles.legal}>
						Terms and Conditions | Copyright JPLearning &copy; {currentYear} | Privacy policy
					</p>
				</div>
			</Stack>
		</Container>
	</footer>
);

export default Footer;
