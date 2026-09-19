import React from 'react';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { Cluster } from '@/components/shared/layout';
import styles from './RadicalItem.module.css';

interface RadicalItemProps {
	entityId: number;
	detailIdentifier: string;
	radical: string | null;
	strokes: number | null;
	meaning: string | null;
	hiragana: string | null;
}

const RadicalItem: React.FC<RadicalItemProps> = ({ detailIdentifier, radical, strokes, meaning, hiragana }) => {
	return (
		<li className={styles.item}>
			<h1 lang="ja">{radical ?? ''}</h1>
			<h3 lang="ja">{hiragana ?? ''}</h3>
			<Cluster justify="between" align="start" gap="md">
				<p>
					meaning: {meaning ?? ''}, strokes: {strokes ?? ''}
				</p>
				<Link to={`/radical/${detailIdentifier}`} aria-label="Open radical">
					<Icon size="sm" name="externalLink" />
				</Link>
			</Cluster>
		</li>
	);
};

export default RadicalItem;
