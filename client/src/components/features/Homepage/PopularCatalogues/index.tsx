import * as React from 'react';
import type { FetchCataloguesFilters } from '@/api/catalogues/catalogues';
import { useCatalogueIndex } from '@/api/generated/catalogue/catalogue';
import type { CatalogueResource } from '@/api/generated/model';
import {
	CompactListLink,
	CompactListRow,
	CompactListSection,
	type CompactListStatus,
} from '@/components/features/Homepage/CompactList';
import { CATALOGUE_ROUTES, resolveCatalogueTypeLabel } from '@/shared/constants/catalogues';
import styles from './PopularCatalogues.module.css';

export const POPULAR_CATALOGUES_FILTERS: FetchCataloguesFilters = {
	per_page: 4,
	public_only: true,
	custom_only: true,
	sort_by: 'views',
	sort_dir: 'desc',
	include_stats_counts: true,
};

const numberFormat = new Intl.NumberFormat('en-US');

const countLabel = (count: number, singular: string) =>
	`${numberFormat.format(count)} ${count === 1 ? singular : `${singular}s`}`;

/** "{items} items · {views} views"; views are left out when the engagement block is missing. */
export const catalogueMeta = (catalogue: CatalogueResource): string => {
	const parts = [countLabel(catalogue.items_count, 'item')];
	const views = Number(catalogue.engagement?.views_count);
	if (catalogue.engagement && Number.isFinite(views)) parts.push(countLabel(views, 'view'));
	return parts.join(' · ');
};

export interface PopularCataloguesViewProps {
	status: CompactListStatus;
	catalogues: CatalogueResource[];
	total?: number;
	onRetry: () => void;
	className?: string;
}

export const PopularCataloguesView: React.FC<PopularCataloguesViewProps> = ({
	status,
	catalogues,
	total,
	onRetry,
	className,
}) => (
	<CompactListSection
		title="Popular catalogues"
		allHref={CATALOGUE_ROUTES.list}
		allNoun="catalogues"
		total={total}
		status={status}
		isEmpty={catalogues.length === 0}
		emptyText="No public catalogues yet"
		errorText="Couldn't load catalogues"
		onRetry={onRetry}
		className={className}
	>
		{catalogues.map((catalogue) => (
			<CompactListRow key={catalogue.uuid}>
				<div className={styles.top}>
					<span className={styles.type}>
						{catalogue.type_label ?? resolveCatalogueTypeLabel(catalogue.type)}
					</span>
					<CompactListLink to={CATALOGUE_ROUTES.detail(catalogue.uuid)} className={styles.title}>
						{catalogue.title}
					</CompactListLink>
				</div>
				<span className={styles.meta}>{catalogueMeta(catalogue)}</span>
			</CompactListRow>
		))}
	</CompactListSection>
);

/** The four most viewed public custom catalogues. */
export const PopularCatalogues: React.FC<{ className?: string }> = ({ className }) => {
	const { data, status, refetch } = useCatalogueIndex(POPULAR_CATALOGUES_FILTERS);

	return (
		<PopularCataloguesView
			status={status}
			catalogues={data?.items ?? []}
			total={data?.pagination.total}
			onRetry={() => void refetch()}
			className={className}
		/>
	);
};
