import React from 'react';
import { Icon } from '@/components/shared/Icon';
import { Link } from '@/components/shared/Link';
import { Cluster } from '@/components/shared/layout';
import styles from './SentenceItem.module.css';

interface SentenceItemProps {
	detailIdentifier: string;
	sentence: string;
	tatoeba_entry?: string | number;
	userId?: string | number;
}

const SentenceItem: React.FC<SentenceItemProps> = ({ detailIdentifier, sentence, tatoeba_entry, userId }) => {
	return (
		<li className={styles.item}>
			<h3>{sentence}</h3>
			<Cluster justify="between" align="start" gap="md">
				{userId ? (
					<p>UserAuthor - {userId}</p>
				) : (
					<p>
						Tatoeba entry -{' '}
						<a
							href={`https://tatoeba.org/eng/sentences/show/${tatoeba_entry}`}
							target="_blank"
							rel="noopener noreferrer"
						>
							{tatoeba_entry}
						</a>
					</p>
				)}
				<Link to={`/sentence/${detailIdentifier}`} aria-label="Open sentence">
					<Icon size="sm" name="externalLink" />
				</Link>
			</Cluster>
		</li>
	);
};

export default SentenceItem;
