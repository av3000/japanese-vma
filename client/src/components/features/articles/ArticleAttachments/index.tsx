import React from 'react';
import { articleKanjiFilters, articleWordFilters } from '@/api/articles/attachments';
import { useInfiniteKanjis } from '@/api/kanjis/hooks/useInfiniteKanjis';
import { useInfiniteWords } from '@/api/words/hooks/useInfiniteWords';
import { Button } from '@/components/shared/Button';
import styles from './ArticleAttachments.module.scss';

/**
 * The kanji and vocabulary processing found in an article.
 *
 * Both lists are the ordinary kanji and word indexes filtered by `article_uuid` (#268), not an
 * article-scoped endpoint: the indexes already page, filter and can carry viewer catalogue
 * state, so a second route would only have duplicated them. They used to ride along inside the
 * article detail response, which made every detail read — and every refetch the processing
 * socket triggered — carry every word the article had ever matched, for lists nothing on the
 * page was showing.
 */

interface ArticleAttachmentsProps {
	articleUuid: string;
}

interface AttachmentSectionProps {
	title: string;
	total: number;
	emptyMessage: string;
	isLoading: boolean;
	isError: boolean;
	hasNextPage: boolean;
	isFetchingNextPage: boolean;
	onLoadMore: () => void;
	children: React.ReactNode;
	hasItems: boolean;
}

const AttachmentSection: React.FC<AttachmentSectionProps> = ({
	title,
	total,
	emptyMessage,
	isLoading,
	isError,
	hasNextPage,
	isFetchingNextPage,
	onLoadMore,
	children,
	hasItems,
}) => {
	const headingId = `article-attachments-${title.toLowerCase()}`;

	return (
		<section className={styles.section} aria-labelledby={headingId}>
			<h3 className={styles.heading} id={headingId}>
				{title}
				{hasItems && <span className={styles.count}>{total}</span>}
			</h3>

			{isLoading && <p className={styles.empty}>Loading {title.toLowerCase()}…</p>}

			{isError && !isLoading && (
				<p className={styles.empty} role="status">
					{title} could not be loaded. Reload the page to try again.
				</p>
			)}

			{!isLoading && !isError && !hasItems && <p className={styles.empty}>{emptyMessage}</p>}

			{hasItems && <ul className={styles.items}>{children}</ul>}

			{hasNextPage && (
				<Button className={styles.more} variant="ghost" onClick={onLoadMore} disabled={isFetchingNextPage}>
					{isFetchingNextPage ? 'Loading…' : `Show more ${title.toLowerCase()}`}
				</Button>
			)}
		</section>
	);
};

export const ArticleAttachments: React.FC<ArticleAttachmentsProps> = ({ articleUuid }) => {
	const kanjis = useInfiniteKanjis({ filters: articleKanjiFilters(articleUuid), enabled: Boolean(articleUuid) });
	const words = useInfiniteWords({ filters: articleWordFilters(articleUuid), enabled: Boolean(articleUuid) });

	return (
		<div className={styles.attachments}>
			<AttachmentSection
				title="Kanji"
				total={kanjis.total}
				hasItems={kanjis.kanjis.length > 0}
				emptyMessage="No kanji have been attached to this article yet."
				isLoading={kanjis.isPending}
				isError={kanjis.isError}
				hasNextPage={Boolean(kanjis.hasNextPage)}
				isFetchingNextPage={kanjis.isFetchingNextPage}
				onLoadMore={() => void kanjis.fetchNextPage()}
			>
				{kanjis.kanjis.map((kanji) => (
					<li className={styles.item} key={kanji.uuid}>
						<span lang="ja">{kanji.character}</span>
						{kanji.meanings && <span className={styles.reading}>{kanji.meanings}</span>}
					</li>
				))}
			</AttachmentSection>

			<AttachmentSection
				title="Vocabulary"
				total={words.total}
				hasItems={words.words.length > 0}
				emptyMessage="No vocabulary has been attached to this article yet."
				isLoading={words.isPending}
				isError={words.isError}
				hasNextPage={Boolean(words.hasNextPage)}
				isFetchingNextPage={words.isFetchingNextPage}
				onLoadMore={() => void words.fetchNextPage()}
			>
				{words.words.map((word) => (
					<li className={styles.item} key={word.uuid}>
						<span lang="ja">{word.word}</span>
						{word.furigana && <span className={styles.reading}>{word.furigana}</span>}
					</li>
				))}
			</AttachmentSection>
		</div>
	);
};

export default ArticleAttachments;
