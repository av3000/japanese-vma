import React from 'react';
import { Link } from 'react-router-dom';
import classNames from 'classnames';
import {
	DASHBOARD_TAB_LABELS,
	DASHBOARD_TABS,
	dashboardTabSearch,
	type DashboardTab,
} from '@/routes/Dashboard/dashboardSearchParams';
import styles from './DashboardTabs.module.css';

export interface DashboardTabsProps {
	active: DashboardTab;
	className?: string;
}

/**
 * The dashboard's sections as links, not ARIA tabs: each section is its own URL, so back,
 * reload and open-in-new-tab work. The current one carries `aria-current="page"`.
 */
export const DashboardTabs: React.FC<DashboardTabsProps> = ({ active, className }) => (
	<nav aria-label="Dashboard sections" className={classNames(styles.tabs, className)}>
		<ul className={styles.list}>
			{DASHBOARD_TABS.map((tab) => {
				const isActive = tab === active;

				return (
					<li key={tab}>
						<Link
							to={{ search: dashboardTabSearch(tab) }}
							className={classNames(styles.tab, isActive && styles.tabActive)}
							aria-current={isActive ? 'page' : undefined}
						>
							{DASHBOARD_TAB_LABELS[tab]}
						</Link>
					</li>
				);
			})}
		</ul>
	</nav>
);

export default DashboardTabs;
