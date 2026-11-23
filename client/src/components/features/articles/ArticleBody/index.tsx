import * as React from 'react';
import classNames from 'classnames';
import styles from './ArticleBody.module.css';

/**
 * Splits stored article text into paragraphs at its line breaks, dropping blank lines. Only
 * ASCII spaces and tabs are trimmed, so a leading ideographic space (Japanese paragraph indent)
 * stays. The text is rendered as text, never as HTML.
 */
export const toParagraphs = (text: string | null | undefined): string[] =>
	(text ?? '')
		.split(/\r\n|\r|\n/)
		.map((line) => line.replace(/^[ \t]+|[ \t]+$/g, ''))
		.filter((line) => line.length > 0);

export interface ArticleTitleProps {
	titleJp: string;
	titleEn?: string | null;
	className?: string;
}

/** The page's `<h1>` in the Japanese serif, with the English title as a muted subtitle. */
export const ArticleTitle: React.FC<ArticleTitleProps> = ({ titleJp, titleEn, className }) => (
	<div className={classNames(styles.titleBlock, className)}>
		<h1 className={styles.title} lang="ja">
			{titleJp}
		</h1>
		{titleEn?.trim() ? (
			<p className={styles.subtitle} lang="en">
				{titleEn}
			</p>
		) : null}
	</div>
);

export interface ArticleBodyProps {
	contentJp: string;
	contentEn?: string | null;
	/** Between the Japanese text and the translation, e.g. an Imported Article's `ArticleAttribution`. */
	attribution?: React.ReactNode;
	className?: string;
}

/**
 * The reading column of Article detail: the Japanese text as paragraphs, then the attribution
 * when there is one, then the English translation in a disclosure that starts closed, so the
 * Japanese is read first.
 */
export const ArticleBody: React.FC<ArticleBodyProps> = ({ contentJp, contentEn, attribution, className }) => {
	const paragraphs = toParagraphs(contentJp);
	const translation = toParagraphs(contentEn);

	return (
		<div className={classNames(styles.body, className)}>
			<div className={styles.text} lang="ja">
				{paragraphs.map((paragraph, index) => (
					<p key={index} className={styles.paragraph}>
						{paragraph}
					</p>
				))}
			</div>
			{attribution ? <div className={styles.attribution}>{attribution}</div> : null}
			{translation.length > 0 ? (
				<details className={styles.translation}>
					<summary className={styles.summary}>English translation</summary>
					<div className={styles.translationText} lang="en">
						{translation.map((paragraph, index) => (
							<p key={index} className={styles.translationParagraph}>
								{paragraph}
							</p>
						))}
					</div>
				</details>
			) : null}
		</div>
	);
};

export default ArticleBody;
