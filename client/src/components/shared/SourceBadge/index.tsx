import React from 'react';
import { ArticleOrigin } from '@/api/generated/model/articleOrigin';
import type { ArticleSourceResource } from '@/api/generated/model/articleSourceResource';
import { Badge } from '@/components/ui/badge';

interface ProvenanceFields {
	origin: ArticleOrigin;
	source: ArticleSourceResource | null;
}

/**
 * The Content Source to credit, or null for an article a person wrote. An imported article whose
 * source row was removed has no source to credit either.
 */
export const importedSourceOf = (article: ProvenanceFields): ArticleSourceResource | null =>
	article.origin === ArticleOrigin.imported ? article.source : null;

export interface SourceBadgeProps {
	source: ArticleSourceResource;
	className?: string;
}

/**
 * Marks an Imported Article with the name of its source. The label is text, not a colour cue.
 */
export const SourceBadge: React.FC<SourceBadgeProps> = ({ source, className }) => (
	<Badge variant="outline" className={className} title={`Imported from ${source.name}`}>
		{source.name}
	</Badge>
);

export default SourceBadge;
