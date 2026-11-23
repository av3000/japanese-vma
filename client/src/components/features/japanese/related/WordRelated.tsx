import * as React from 'react';
import { useRef } from 'react';
import { useWordArticlePages, wordArticleFilters } from '@/api/articles/related';
import type { KanjiResource } from '@/api/generated/model/kanjiResource';
import { RelatedListModal, RelatedPreview } from '@/components/features/japanese/dictionaryList';
import { useModal } from '@/hooks/useModal';
import { EntryKanji } from './EntryKanji';
import styles from './Related.module.css';
import { RelatedArticleList } from './RelatedArticleList';
import { ArticleListModalBody, RELATED_PREVIEW_ROWS } from './RelatedArticles';

interface WordRelatedProps {
	word: { id: number; word: string; kanjis: KanjiResource[] };
}

/**
 * The kanji a word is written with (the whole list, from the payload) and the articles that use it
 * (five, then all of them in a modal with numbered pages and a search).
 */
export const WordRelated: React.FC<WordRelatedProps> = ({ word }) => {
	const articleDialogRef = useRef<HTMLDialogElement | null>(null);
	const articleModal = useModal(articleDialogRef, { id: 'word-articles-modal' });
	const articles = useWordArticlePages(word.id);

	return (
		<div className={styles.sections}>
			<EntryKanji
				title="Kanji in this word"
				text={word.word}
				kanjis={word.kanjis}
				emptyMessage="This word is written without kanji."
			/>
			<RelatedPreview
				title="Articles with this word"
				noun="articles"
				total={articles.total}
				isPending={articles.isPending}
				isError={articles.isError}
				isEmpty={articles.rows.length === 0}
				emptyMessage="No articles use this word yet."
				controller={articleModal}
			>
				<RelatedArticleList articles={articles.rows.slice(0, RELATED_PREVIEW_ROWS)} />
			</RelatedPreview>
			<RelatedListModal controller={articleModal} title="Articles with this word">
				<ArticleListModalBody
					filters={wordArticleFilters(word.id)}
					emptyMessage="No articles use this word yet."
				/>
			</RelatedListModal>
		</div>
	);
};

export default WordRelated;
