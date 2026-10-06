import React, { useCallback } from 'react';
import { useSearchParams } from 'react-router-dom';
import { useDashboardCounts, type DashboardCounts } from '@/api/dashboard/useDashboardCounts';
import { DashboardTabs } from '@/components/features/dashboard/DashboardTabs';
import { formatCount } from '@/components/features/dashboard/dashboardValues';
import { PageHeader } from '@/components/shared/PageHeader';
import { PageLoading } from '@/components/shared/PageLoading';
import { Container, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import styles from './Dashboard.module.css';
import DashboardArticlesPanel from './DashboardArticlesPanel';
import DashboardCataloguesPanel from './DashboardCataloguesPanel';
import DashboardReviewPanel from './DashboardReviewPanel';
import {
	parseDashboardSearchParams,
	serializeDashboardViewState,
	type DashboardViewChange,
} from './dashboardSearchParams';

const plural = (count: number, one: string, many: string) => `${formatCount(count)} ${count === 1 ? one : many}`;

/** "12 articles · 6 lists · 2 awaiting review", or nothing until the counts arrive. */
export const dashboardCountsMeta = (counts: DashboardCounts | undefined): string | undefined =>
	counts
		? [
				plural(counts.articles, 'article', 'articles'),
				plural(counts.lists, 'list', 'lists'),
				`${formatCount(counts.awaitingReview)} awaiting review`,
			].join(' · ')
		: undefined;

/**
 * The signed-in user's own articles and lists as Index tables (UI-DASH, #348). The URL owns the
 * open tab and its filters; each tab owns its request.
 */
const Dashboard: React.FC = () => {
	const { isLoading, user } = useAuth();
	const [searchParams, setSearchParams] = useSearchParams();
	const isAdmin = Boolean(user?.isAdmin);
	const counts = useDashboardCounts(user?.uuid);

	const updateView = useCallback<DashboardViewChange>(
		(patch, { replace = false } = {}) => {
			setSearchParams(
				(current) =>
					serializeDashboardViewState({ ...parseDashboardSearchParams(current, { isAdmin }), ...patch }),
				{ replace },
			);
		},
		[setSearchParams, isAdmin],
	);

	if (isLoading || !user) {
		return <PageLoading family="dashboard" />;
	}

	const view = parseDashboardSearchParams(searchParams, { isAdmin });

	return (
		<Container as="section" className={styles.page}>
			<Stack gap="lg">
				<PageHeader title="Dashboard" meta={dashboardCountsMeta(counts.data)} />
				<DashboardTabs active={view.tab} isAdmin={isAdmin} />
				{view.tab === 'articles' ? (
					<DashboardArticlesPanel user={user} view={view} onViewChange={updateView} />
				) : view.tab === 'lists' ? (
					<DashboardCataloguesPanel user={user} view={view} onViewChange={updateView} />
				) : (
					<DashboardReviewPanel enabled={isAdmin} />
				)}
			</Stack>
		</Container>
	);
};

export default Dashboard;
