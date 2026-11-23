import * as React from 'react';
import { type RelatedArticleFilters, useRelatedArticlePages } from '@/api/articles/related';
import { PagedControls, PagedListFilters } from '@/components/features/japanese/dictionaryList';
import styles from './Related.module.css';
import { RelatedArticleList } from './RelatedArticleList';

/** Rows shown under each related heading; the rest are one click away in the modal. */
export const RELATED_PREVIEW_ROWS = 5;

interface ArticleListModalBodyProps {
	filters: RelatedArticleFilters;
	emptyMessage: string;
}

/**
 * The full list of related articles inside its modal: a search, the list and numbered pages. It
 * reads its own paged list, so searching here never changes the preview on the page.
 */
export const ArticleListModalBody: React.FC<ArticleListModalBodyProps> = ({ filters, emptyMessage }) => {
	const list = useRelatedArticlePages(filters);

	const message = list.isPending
		? 'Loading articles…'
		: list.isError
			? 'The articles could not be loaded. Reload the page to try again.'
			: list.keyword
				? `No articles match “${list.keyword}”.`
				: emptyMessage;

	return (
		<>
			<PagedListFilters list={list} noun="articles" placeholder="Words in the title or text" />
			{list.rows.length > 0 ? (
				<RelatedArticleList articles={list.rows} />
			) : (
				<p className={styles.message} role="status">
					{message}
				</p>
			)}
			<PagedControls list={list} noun="articles" label="Article pages" />
		</>
	);
};
