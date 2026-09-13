import React from 'react';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Link } from '@/components/shared/Link';
import { Grid } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';
import styles from './WordItem.module.css';

interface WordItemProps {
	entityId: number;
	detailIdentifier: string;
	word: string;
	furigana: string;
	word_type: string;
	meaning: string;
	jlpt: string;
	isSaved?: boolean;
	isKnown?: boolean;
	onBookmarkStateChange?: (state: { isBookmarked: boolean; isKnown: boolean }) => void;
}

const WordItem: React.FC<WordItemProps> = ({
	entityId,
	detailIdentifier,
	word,
	furigana,
	word_type,
	meaning,
	jlpt,
	isSaved = false,
	isKnown = false,
	onBookmarkStateChange,
}) => {
	const { isAuthenticated } = useAuth();

	return (
		<li className={styles.item}>
			<Link to={`/word/${detailIdentifier}`}>
				<ruby className={styles.reading}>
					{word}
					<rp>(</rp>
					<rt>{furigana}</rt>
					<rp>)</rp>
				</ruby>
			</Link>
			<Grid columns={12} gap="md">
				<Grid.Item span={{ base: 12, sm: 6 }}>
					<p>type: {word_type}</p>
				</Grid.Item>
				<Grid.Item span={{ base: 12, sm: 6 }}>
					<div>
						<div>meaning: {meaning}</div>
						<div>jlpt: {jlpt}</div>
					</div>
					<div>
						{isAuthenticated && (
							<AuthorizedBookmarkWidget
								instanceObjectType={SavedListType.WORDS}
								isKnownType={SavedListType.KNOWNWORDS}
								entityId={entityId}
								modalTitle="Choose Word List to add"
								initialIsBookmarked={isSaved}
								initialIsKnown={isKnown}
								loadOnMount={false}
								onStateChange={onBookmarkStateChange}
							/>
						)}
					</div>
				</Grid.Item>
			</Grid>
		</li>
	);
};

export default WordItem;
