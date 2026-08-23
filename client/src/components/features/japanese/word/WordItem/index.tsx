import React from 'react';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Link } from '@/components/shared/Link';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';

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
		<div className="post-preview">
			<Link className="tag-link" to={`/word/${detailIdentifier}`}>
				<ruby className="h2 mr-2">
					{word}
					<rp>(</rp>
					<rt>{furigana}</rt>
					<rp>)</rp>
				</ruby>
			</Link>
			<div className="row">
				<div className="col-md-6">
					<p>type: {word_type}</p>
				</div>
				<div className="col-md-6">
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
				</div>
			</div>
			<hr />
		</div>
	);
};

export default WordItem;
