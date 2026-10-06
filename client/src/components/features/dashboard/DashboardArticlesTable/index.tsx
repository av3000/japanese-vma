import * as React from 'react';
import { Link } from 'react-router-dom';
import type { ArticleResource } from '@/api/generated/model';
import { Missing } from '@/components/features/japanese/dictionaryList';
import { DataTable, type DataTableColumn, type DataTableEmpty } from '@/components/shared/DataTable';
import { dominantJlptLevel } from '@/components/shared/JlptBar/jlptSegments';
import { LevelBadge } from '@/components/shared/LevelBadge';
import { processingStatusPill, StatusPill } from '@/components/shared/StatusPill';
import {
	actionsCellClassName,
	DateCell,
	RowActions,
	StackedLabel,
	statusCellClassName,
	VisibilityCell,
	VisuallyHidden,
} from '../dashboardCells';
import { approvalColumnPill, approvalHint, formatCount, toCount, visibleProcessingStatus } from '../dashboardValues';
import styles from './DashboardArticlesTable.module.css';

export interface DashboardArticlesTableProps {
	articles: readonly ArticleResource[];
	loading?: boolean;
	empty: DataTableEmpty;
	/** Asks the page to confirm and delete; the table owns no dialog. */
	onDelete: (article: ArticleResource) => void;
}

export const articleEditPath = (uuid: string) => `/articles/${uuid}?edit=1`;

const stat = (article: ArticleResource, key: 'views_count' | 'comments_count' | 'likes_count') =>
	formatCount(toCount(article.engagement?.stats?.[key]));

const baseColumns: DataTableColumn<ArticleResource>[] = [
	{
		id: 'title',
		header: 'Title',
		rowHeader: true,
		cellClassName: styles.titleCell,
		cell: (article) => (
			<>
				<Link to={`/articles/${article.uuid}`} lang="ja" className={styles.title}>
					{article.title_jp}
				</Link>
				{article.title_en ? <span className={styles.subtitle}>{article.title_en}</span> : null}
			</>
		),
	},
	{
		id: 'approval',
		header: 'Approval',
		mobileLabel: 'Approval',
		cellClassName: statusCellClassName,
		cell: (article) => {
			const hint = approvalHint(article.status);

			return (
				<>
					<StatusPill {...approvalColumnPill(article.status)} />
					{hint ? <span className={styles.hint}>{hint}</span> : null}
				</>
			);
		},
	},
	{
		id: 'visibility',
		header: 'Visibility',
		cell: (article) => <VisibilityCell publicity={article.publicity} />,
	},
	{
		id: 'processing',
		header: 'Processing',
		cellClassName: statusCellClassName,
		cell: (article) => {
			const status = visibleProcessingStatus(article.processing_status);

			return status ? (
				<>
					<StackedLabel>Processing</StackedLabel>
					<StatusPill {...processingStatusPill(status)} />
				</>
			) : (
				<VisuallyHidden>Nothing in progress</VisuallyHidden>
			);
		},
	},
	{
		id: 'level',
		header: 'Level',
		mobileLabel: 'Level',
		cell: (article) => {
			const level = dominantJlptLevel(article.jlpt_levels);

			return level ? <LevelBadge level={level} size="sm" /> : <Missing label="No level yet" />;
		},
	},
	{
		id: 'views',
		header: 'Views',
		numeric: true,
		mobileLabel: 'Views',
		cell: (article) => stat(article, 'views_count'),
	},
	{
		id: 'comments',
		header: 'Comments',
		numeric: true,
		priority: 'low',
		mobileLabel: 'Comments',
		cell: (article) => stat(article, 'comments_count'),
	},
	{
		id: 'likes',
		header: 'Likes',
		numeric: true,
		priority: 'low',
		mobileLabel: 'Likes',
		cell: (article) => stat(article, 'likes_count'),
	},
	{
		id: 'updated',
		header: 'Updated',
		// Low priority: from 768 to 1023px the three status columns need the room.
		priority: 'low',
		mobileLabel: 'Updated',
		cell: (article) => <DateCell iso={article.updated_at} />,
	},
];

/** Title, then the three status facts, then the numbers, then the date and the actions. */
const STACKED = {
	columns: 'auto auto auto minmax(0, 1fr)',
	areas: [
		'title title title title',
		'approval approval approval approval',
		'visibility processing processing processing',
		'level views comments likes',
		'updated updated actions actions',
	],
};

/**
 * The signed-in user's articles as an Index table (UI-DASH-03, #453). Approval, visibility and
 * processing are three separate facts in three columns, each with visible text.
 */
export const DashboardArticlesTable: React.FC<DashboardArticlesTableProps> = ({
	articles,
	loading,
	empty,
	onDelete,
}) => {
	// Stable while `onDelete` is, so untouched rows of a long list do not re-render.
	const columns = React.useMemo<DataTableColumn<ArticleResource>[]>(
		() => [
			...baseColumns,
			{
				id: 'actions',
				header: 'Actions',
				headerHidden: true,
				cellClassName: actionsCellClassName,
				cell: (article) => (
					<RowActions
						name={article.title_jp}
						editTo={articleEditPath(article.uuid)}
						onDelete={() => onDelete(article)}
					/>
				),
			},
		],
		[onDelete],
	);

	return (
		<DataTable
			label="Your articles"
			columns={columns}
			rows={articles}
			getRowKey={(article) => article.uuid}
			loading={loading}
			empty={empty}
			stacked={STACKED}
		/>
	);
};

export default DashboardArticlesTable;
