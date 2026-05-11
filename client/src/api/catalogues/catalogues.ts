import type { CatalogueDetailResource } from '@/api/generated/model/catalogueDetailResource';
import type { CatalogueIndexParams } from '@/api/generated/model/catalogueIndexParams';
import type { CatalogueResource } from '@/api/generated/model/catalogueResource';
import type { EngagementStatsResource } from '@/api/generated/model/engagementStatsResource';
import type { HashtagResource } from '@/api/generated/model/hashtagResource';

export type Catalogue = CatalogueResource;
export type CatalogueDetails = CatalogueDetailResource;
export type FetchCataloguesFilters = Omit<CatalogueIndexParams, 'page'>;

export interface CatalogueArticleItem {
	id: number;
	uuid: string;
	title_jp: string;
	hashtags: HashtagResource[];
	engagement: EngagementStatsResource | null;
	saves_count: number;
}
