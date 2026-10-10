import React from 'react';
import type { ArticleSourceResource } from '@/api/generated/model/articleSourceResource';
import { Link } from '@/components/shared/Link';
import styles from './ArticleAttribution.module.css';

export interface ArticleAttributionProps {
	source: ArticleSourceResource;
	sourceLink: string;
}

/**
 * Credit line for an Imported Article. Only an excerpt is stored, so the full article is one
 * prominent link away, on the publisher's own site.
 */
export const ArticleAttribution: React.FC<ArticleAttributionProps> = ({ source, sourceLink }) => (
	<aside className={styles.attribution} aria-label="Article source">
		<p className={styles.credit}>
			This is an excerpt from <strong>{source.name}</strong>, imported automatically. All rights belong to the
			publisher.
		</p>
		<Link linkUrl={sourceLink} rel="noopener noreferrer" weight="bold">
			{`Read the full article on ${source.name}`}
		</Link>
	</aside>
);

export default ArticleAttribution;
