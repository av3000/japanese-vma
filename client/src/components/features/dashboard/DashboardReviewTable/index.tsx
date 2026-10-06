import * as React from 'react';
import { Link } from 'react-router-dom';
import type { ArticleModerationItemResource } from '@/api/generated/model';
import { Missing } from '@/components/features/japanese/dictionaryList';
import { DataTable, type DataTableColumn, type DataTableEmpty } from '@/components/shared/DataTable';
import { StatusPill } from '@/components/shared/StatusPill';
import { DateCell, statusCellClassName } from '../dashboardCells';
import { approvalColumnPill } from '../dashboardValues';
import styles from './DashboardReviewTable.module.css';

export interface DashboardReviewTableProps {
	articles: readonly ArticleModerationItemResource[];
	loading?: boolean;
	empty: DataTableEmpty;
}

const columns: DataTableColumn<ArticleModerationItemResource>[] = [
	{
		id: 'title',
		header: 'Title',
		rowHeader: true,
		width: 'fill',
		cellClassName: styles.titleCell,
		// The review itself happens on the article page (ArticleReviewModal).
		cell: (article) => (
			<Link to={`/articles/${article.uuid}`} lang="ja" className={styles.title}>
				{article.title_jp}
			</Link>
		),
	},
	{
		id: 'approval',
		header: 'Approval',
		mobileLabel: 'Approval',
		cellClassName: statusCellClassName,
		cell: (article) => <StatusPill {...approvalColumnPill(article.status)} />,
	},
	{
		id: 'tags',
		header: 'Tags',
		mobileLabel: 'Tags',
		cell: (article) =>
			article.hashtags.length > 0 ? (
				<span className={styles.tags}>{article.hashtags.map((tag) => tag.content).join(', ')}</span>
			) : (
				<Missing label="No tags" />
			),
	},
	{
		id: 'submitted',
		header: 'Submitted',
		mobileLabel: 'Submitted',
		cell: (article) => <DateCell iso={article.created_at} />,
	},
];

const STACKED = {
	columns: 'auto minmax(0, 1fr)',
	areas: ['title title', 'approval submitted', 'tags tags'],
};

/**
 * Articles awaiting review, for admins (UI-DASH-05, #455). Temporary: delete this table and the
 * dashboard's Review tab when the Filament moderation resource ships (#184).
 */
export const DashboardReviewTable: React.FC<DashboardReviewTableProps> = ({ articles, loading, empty }) => (
	<DataTable
		label="Articles awaiting review"
		columns={columns}
		rows={articles}
		getRowKey={(article) => article.uuid}
		loading={loading}
		empty={empty}
		stacked={STACKED}
	/>
);

export default DashboardReviewTable;
