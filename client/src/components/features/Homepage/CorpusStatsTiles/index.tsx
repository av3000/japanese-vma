import * as React from 'react';
import { Link } from 'react-router-dom';
import classNames from 'classnames';
import { useCorpusStatsShow } from '@/api/generated/corpus-stats/corpus-stats';
import type { CorpusStatsResource } from '@/api/generated/model';
import { Grid } from '@/components/shared/layout';
import styles from './CorpusStatsTiles.module.css';

export const CORPUS_TILES: ReadonlyArray<{ key: keyof CorpusStatsResource; label: string; to: string }> = [
	{ key: 'radicals', label: 'Radicals', to: '/radicals' },
	{ key: 'kanjis', label: 'Kanji', to: '/kanjis' },
	{ key: 'words', label: 'Words', to: '/words' },
	{ key: 'sentences', label: 'Sentences', to: '/sentences' },
];

const countFormat = new Intl.NumberFormat('en-US');

const COLUMNS = { base: 2, sm: 4 };

export type CorpusStatsTilesViewProps = (
	| { status: 'pending' }
	| { status: 'error' }
	| { status: 'success'; stats: CorpusStatsResource }
) & { className?: string };

/**
 * Four linked tiles with the corpus totals. While loading, skeleton tiles of the final size hold
 * the space; on error the row is not rendered at all.
 */
export const CorpusStatsTilesView: React.FC<CorpusStatsTilesViewProps> = (props) => {
	if (props.status === 'error') return null;

	if (props.status === 'pending') {
		return (
			<Grid
				as="ul"
				columns={COLUMNS}
				gap="sm"
				className={classNames(styles.tiles, props.className)}
				aria-hidden="true"
			>
				{CORPUS_TILES.map((tile) => (
					<Grid.Item as="li" span="auto" key={tile.key}>
						<span className={classNames(styles.tile, styles.skeleton)} data-testid="corpus-tile-skeleton" />
					</Grid.Item>
				))}
			</Grid>
		);
	}

	return (
		<Grid as="ul" columns={COLUMNS} gap="sm" className={classNames(styles.tiles, props.className)}>
			{CORPUS_TILES.map((tile) => (
				<Grid.Item as="li" span="auto" key={tile.key}>
					<Link to={tile.to} className={styles.tile}>
						{/* The space keeps the accessible name "214 Radicals"; flex layout drops it visually. */}
						<span className={styles.count}>{countFormat.format(props.stats[tile.key])}</span>{' '}
						<span className={styles.label}>{tile.label}</span>
					</Link>
				</Grid.Item>
			))}
		</Grid>
	);
};

/** Landing-page corpus tiles, fed by the generated `/v1/japanese-material/stats` hook. */
export const CorpusStatsTiles: React.FC<{ className?: string }> = ({ className }) => {
	const { data, status } = useCorpusStatsShow();

	return status === 'success' ? (
		<CorpusStatsTilesView status="success" stats={data} className={className} />
	) : (
		<CorpusStatsTilesView status={status} className={className} />
	);
};
