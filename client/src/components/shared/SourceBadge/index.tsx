import React from 'react';
import type { ArticleSourceResource } from '@/api/generated/model/articleSourceResource';
import { Badge } from '@/components/ui/badge';
import styles from './SourceBadge.module.css';

export interface SourceBadgeProps {
	source: ArticleSourceResource;
	className?: string;
}

/**
 * Marks an Imported Article with the name of its source. The label is text, not a colour cue, and
 * assistive technology hears "Imported from" before it, which the tooltip alone would not give.
 */
export const SourceBadge: React.FC<SourceBadgeProps> = ({ source, className }) => (
	<Badge variant="outline" className={className} title={`Imported from ${source.name}`}>
		<span className={styles.visuallyHidden}>Imported from </span>
		{source.name}
	</Badge>
);

export default SourceBadge;
