import React from 'react';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Link } from '@/components/shared/Link';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';

interface WordItemProps {
	id: string | number;
	word: string;
	furigana: string;
	word_type: string;
	meaning: string;
	jlpt: string;
}

const WordItem: React.FC<WordItemProps> = ({ id, word, furigana, word_type, meaning, jlpt }) => {
	const { isAuthenticated } = useAuth();

	const entityId = Number(id);

	return (
		<div className="post-preview">
			<Link className="tag-link" to={`/word/${id}`}>
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
