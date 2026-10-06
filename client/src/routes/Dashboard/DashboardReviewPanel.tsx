import React from 'react';
import { usePendingArticles } from '@/api/articles/moderation';
import { DashboardReviewTable } from '@/components/features/dashboard/DashboardReviewTable';
import { DASHBOARD_PER_PAGE, DashboardListSection } from './DashboardListSection';

const PENDING_FILTERS = { per_page: DASHBOARD_PER_PAGE };

const EMPTY = { title: 'Nothing awaits review', hint: 'New and resubmitted articles appear here.' };

/**
 * The Review tab, for admins only: the moderation queue, reviewed one article at a time on its
 * page. Temporary until the Filament moderation resource ships (#184).
 */
const DashboardReviewPanel: React.FC<{ enabled: boolean }> = ({ enabled }) => {
	const { pendingArticles, ...query } = usePendingArticles({ enabled, filters: PENDING_FILTERS });

	return (
		<DashboardListSection
			noun="articles awaiting review"
			subject="The review queue"
			itemCount={pendingArticles.length}
			query={query}
		>
			{({ loading }) => <DashboardReviewTable articles={pendingArticles} loading={loading} empty={EMPTY} />}
		</DashboardListSection>
	);
};

export default DashboardReviewPanel;
