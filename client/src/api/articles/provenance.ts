import { ArticleOrigin } from '@/api/generated/model/articleOrigin';
import type { ArticleSourceResource } from '@/api/generated/model/articleSourceResource';

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
