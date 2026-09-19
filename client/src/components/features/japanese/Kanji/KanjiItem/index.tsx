import React from 'react';
import { AuthorizedBookmarkWidget } from '@/components/features/catalogues/AuthorizedBookmarkWidget';
import { LevelBadge } from '@/components/shared/LevelBadge';
import { Link } from '@/components/shared/Link';
import { Cluster, Grid } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import { SavedListType } from '@/shared/constants/enums';
import styles from './KanjiItem.module.css';

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
		<li className={styles.item}>
			<h1 lang="ja">{character}</h1>
			<h3>{meaning}</h3>
			<Grid columns={12} gap="md">
				<Grid.Item span={{ base: 12, sm: 6 }}>
					<div>onyomi: {onyomi},</div>
					<div>kunyomi: {kunyomi}</div>
				</Grid.Item>
				<Grid.Item span={{ base: 12, sm: 3 }}>
					<div>frequency: {frequency},</div>
					<div>
						jlpt: <LevelBadge level={jlpt} size="sm" />
					</div>
				</Grid.Item>
				<Grid.Item span={{ base: 12, sm: 3 }}>
					<div>parts: {parts},</div>
					<div>stroke_count: {strokeCount}</div>
					<Cluster justify="end">
						<Link to={`/kanji/${uuid}`}>Open</Link>
					</Cluster>
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
				</Grid.Item>
			</Grid>
		</li>
	);
};

export default KanjiItem;
