import React, { useCallback, useState } from 'react';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { PageLoading } from '@/components/shared/PageLoading';
import { Cluster, Container, Stack } from '@/components/shared/layout';
import { useAuth } from '@/hooks/useAuth';
import styles from './Dashboard.module.css';
import DashboardArticlesPanel from './DashboardArticlesPanel';
import DashboardCataloguesPanel from './DashboardCataloguesPanel';
import { DASHBOARD_TYPES, RESOURCE_TYPES, type DashboardType, type ResourceType } from './dashboard.constants';

const Dashboard: React.FC = () => {
	const [currentResource, setCurrentResource] = useState<ResourceType>(RESOURCE_TYPES.LISTS);
	const [dashboardView, setDashboardView] = useState<DashboardType>(DASHBOARD_TYPES.COMMON_USER);
	const { isAuthenticated, isLoading, user: currentUser } = useAuth();

	const toggleResource = useCallback(() => {
		setCurrentResource((prev) => (prev === RESOURCE_TYPES.LISTS ? RESOURCE_TYPES.ARTICLES : RESOURCE_TYPES.LISTS));
	}, []);

	const toggleDashboardView = useCallback(() => {
		setDashboardView((prev) =>
			prev === DASHBOARD_TYPES.COMMON_USER ? DASHBOARD_TYPES.ADMIN : DASHBOARD_TYPES.COMMON_USER,
		);
	}, []);

	if (isLoading) {
		return <PageLoading family="dashboard" />;
	}

	return (
		<Container as="section" className={styles.page}>
			<Stack gap="md">
				<Cluster className={styles.toolbar}>
					<Button variant="ghost" onClick={toggleResource}>
						{currentResource === RESOURCE_TYPES.LISTS ? 'Articles' : 'Lists'}{' '}
						<Icon name="chevron" rotate="270" />
					</Button>
				</Cluster>
				{currentResource === RESOURCE_TYPES.LISTS ? (
					<DashboardCataloguesPanel isAuthenticated={isAuthenticated} currentUser={currentUser} />
				) : (
					<DashboardArticlesPanel
						dashboardView={dashboardView}
						isAuthenticated={isAuthenticated}
						currentUser={currentUser}
						onToggleDashboardView={toggleDashboardView}
					/>
				)}
			</Stack>
		</Container>
	);
};

export default Dashboard;
