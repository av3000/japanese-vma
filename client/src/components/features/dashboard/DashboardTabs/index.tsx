import React from 'react';
import { Link } from 'react-router-dom';
import classNames from 'classnames';
import {
	DASHBOARD_TAB_LABELS,
	dashboardTabSearch,
	visibleDashboardTabs,
	type DashboardTab,
} from '@/routes/Dashboard/dashboardSearchParams';
import styles from './DashboardTabs.module.css';

export interface DashboardTabsProps {
	active: DashboardTab;
	/** Admins also get the review queue, kept until the Filament moderation resource (#184). */
	isAdmin: boolean;
	className?: string;
}

/**
 * The dashboard's sections as links, not ARIA tabs: each section is its own URL, so back,
 * reload and open-in-new-tab work. The current one carries `aria-current="page"`.
 */
export const DashboardTabs: React.FC<DashboardTabsProps> = ({ active, isAdmin, className }) => (
	<nav aria-label="Dashboard sections" className={classNames(styles.tabs, className)}>
		<ul className={styles.list}>
			{visibleDashboardTabs(isAdmin).map((tab) => {
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
