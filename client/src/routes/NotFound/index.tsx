import React from 'react';
import { Button } from '@/components/shared/Button';
import { Cluster, Container } from '@/components/shared/layout';
import styles from './NotFound.module.css';

/**
 * The 404, derived from the Index landing page: one centred column on the kinari page, no card.
 *
 * The Japanese note stays quiet. The only ornament is 迷 (mayou, to lose one's way) as a large
 * muted glyph behind the heading, and the lead ends with 迷子 (maigo, "lost") set like a
 * dictionary entry, the way the rest of the site shows a word. Shu is not used: it means "level".
 */
const PageNotFound: React.FC = () => (
	<Container className={styles.page}>
		<div className={styles.column}>
			<span className={styles.glyph} lang="ja" aria-hidden="true">
				迷
			</span>
			<p className={styles.code}>404</p>
			<h1 className={styles.title}>This page doesn&apos;t exist</h1>
			<p className={styles.lead}>The address may be mistyped, or the page may have moved.</p>
			<p className={styles.entry}>
				<span className={styles.word} lang="ja">
					迷子
				</span>
				<span className={styles.reading} lang="ja">
					まいご
				</span>
				<span className={styles.gloss}>lost; gone astray</span>
			</p>
			<Cluster gap="sm" justify="center" className={styles.actions}>
				<Button to="/" variant="primary">
					Back to home
				</Button>
				<Button to="/articles" variant="outline">
					Browse articles
				</Button>
			</Cluster>
		</div>
	</Container>
);

export default PageNotFound;
