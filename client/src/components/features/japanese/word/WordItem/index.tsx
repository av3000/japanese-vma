import React from 'react';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Icon } from '@/components/shared/Icon';
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
			<ruby className="h2 mr-2">
				{word}
				<rp>(</rp>
				<rt>{furigana}</rt>
				<rp>)</rp>
			</ruby>
			<div className="row">
				<div className="col-md-6">
					<p>type: {word_type}</p>
				</div>
				<div className="col-md-6">
					<p>
						meaning: {meaning},<br /> jlpt: {jlpt}
						<span className="float-right">
							<Link className="tag-link" to={`/word/${id}`}>
								<Icon size="sm" name="externalLink" />
							</Link>
							{isAuthenticated && (
								<AuthorizedBookmarkWidget
									instanceObjectType={SavedListType.WORDS}
									isKnownType={SavedListType.KNOWNWORDS}
									entityId={entityId}
									modalTitle="Choose Word List to add"
								/>
							)}
						</span>
					</p>
				</div>
			</div>
			<hr />
		</div>
	);
};

export default WordItem;
