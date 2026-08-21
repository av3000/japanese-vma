import React from 'react';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { Link } from '@/components/shared/Link';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';

interface KanjiItemProps {
	id: string | number;
	uuid: string;
	character: string;
	strokeCount: number;
	onyomi: string;
	kunyomi: string;
	meaning: string;
	frequency: string;
	jlpt: string;
	parts: string;
	isSaved?: boolean;
	isKnown?: boolean;
	onBookmarkStateChange?: (state: { isBookmarked: boolean; isKnown: boolean }) => void;
}

const KanjiItem: React.FC<KanjiItemProps> = ({
	id,
	uuid,
	character,
	strokeCount,
	onyomi,
	kunyomi,
	meaning,
	frequency,
	jlpt,
	parts,
	isSaved = false,
	isKnown = false,
	onBookmarkStateChange,
}) => {
	const { isAuthenticated } = useAuth();
	const entityId = Number(id);

	return (
		<div className="post-preview">
			<div className="post-title">
				<h1>{character}</h1>
			</div>
			<div className="post-subtitle">
				<h3>{meaning}</h3>
			</div>
			<div className="row">
				<div className="col-md-6">
					<div>onyomi: {onyomi},</div>
					<div>kunyomi: {kunyomi}</div>
				</div>
				<div className="col-md-3">
					<div>frequency: {frequency},</div>
					<div>jlpt: {jlpt}</div>
				</div>
				<div className="col-md-3">
					<div>parts: {parts},</div>
					<div>stroke_count: {strokeCount}</div>
					<div className="float-right">
						<Link to={`/kanji/${uuid}`}>Open</Link>
					</div>
					{isAuthenticated && (
						<AuthorizedBookmarkWidget
							instanceObjectType={SavedListType.KANJIS}
							isKnownType={SavedListType.KNOWNKANJIS}
							entityId={entityId}
							modalTitle="Choose Kanji List to add"
							initialIsBookmarked={isSaved}
							initialIsKnown={isKnown}
							loadOnMount={false}
							onStateChange={onBookmarkStateChange}
						/>
					)}
				</div>
			</div>
			<hr />
		</div>
	);
};

export default KanjiItem;
