import classNames from 'classnames';
import type { StudyCard } from '@/api/flashcards/deck';
import type { FlashcardField } from '@/api/generated/model/flashcardField';
import { LevelBadge } from '@/components/shared/LevelBadge';
import { Cluster } from '@/components/shared/layout';
import styles from './FlashcardPrompt.module.css';

export interface FlashcardPromptProps {
	card: StudyCard;
	/** Which field the prompt shows; Japanese fields get the Japanese font and `lang="ja"`. */
	field: FlashcardField;
	className?: string;
}

const isJapaneseField = (field: FlashcardField) => field !== 'meaning';

/**
 * The question side of a card: the prompt large, then JLPT level, stroke count, grade and
 * the item's hint on one muted line. The glyph is the only big element on the page, so a
 * single character and a twelve-character word both fit at 360px.
 */
export const FlashcardPrompt = ({ card, field, className }: FlashcardPromptProps) => {
	const japanese = isJapaneseField(field);
	const isSingleGlyph = japanese && Array.from(card.promptText).length === 1;

	return (
		<div className={classNames(styles.prompt, className)}>
			<p
				className={classNames(styles.text, japanese && styles.japanese, isSingleGlyph && styles.glyph)}
				lang={japanese ? 'ja' : undefined}
			>
				{card.promptText}
			</p>
			<Cluster as="p" gap="xs" align="center" className={styles.meta}>
				{card.jlpt !== null && <LevelBadge level={card.jlpt} size="sm" />}
				{card.strokes !== null && (
					<span>
						{card.strokes} {card.strokes === 1 ? 'stroke' : 'strokes'}
					</span>
				)}
				{card.grade !== null && <span>Grade {card.grade}</span>}
				{card.promptHint !== null && <span>{card.promptHint}</span>}
			</Cluster>
		</div>
	);
};

export default FlashcardPrompt;
