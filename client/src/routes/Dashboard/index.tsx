import React, { useCallback, useState } from 'react';
import { Button } from '@/components/shared/Button';
import { Icon } from '@/components/shared/Icon';
import { PageLoading } from '@/components/shared/PageLoading';
import { useAuth } from '@/hooks/useAuth';
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
		<div className="container mt-5">
			<div className="container mt-5">
				<div className="ml-3 mt-2">
					<div className="row align-items-center">
						<div className="col-auto">
							<Button variant="ghost" onClick={toggleResource}>
								{currentResource === RESOURCE_TYPES.LISTS ? 'Articles' : 'Lists'}{' '}
								<Icon name="chevron" rotate="270" />
							</Button>
						</div>
					</div>
				</div>
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
			</div>
		</div>
	);
};

export default Dashboard;
