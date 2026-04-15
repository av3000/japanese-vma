import axios from '@/services/axios';
import type {
	AuthorResource,
	CatalogueDetailResourceCatalogue,
	CatalogueListResource,
	CatalogueResource,
	EngagementStatsResource,
	HashtagResource,
} from '@/api/generated/model';

export type CatalogueHashtag = Pick<HashtagResource, 'id' | 'content'>;
export type CatalogueEngagementStats = EngagementStatsResource;
export type CatalogueOwner = AuthorResource;
export type Catalogue = CatalogueResource;
export type CatalogueDetails = CatalogueDetailResourceCatalogue;
export type CataloguesResponse = CatalogueListResource;

export interface FetchCataloguesFilters {
	search?: string;
	sort_by?: 'created_at' | 'views';
	sort_dir?: 'asc' | 'desc';
	per_page?: number;
	owner_uid?: string;
	type?: number;
	public_only?: boolean;
	custom_only?: boolean;
	include_stats_counts?: boolean;
	include_hashtags?: boolean;
}

export const fetchCatalogues = async (filters: FetchCataloguesFilters = {}, pageParam = 1) => {
	const params = { ...filters, page: pageParam };
	const response = await axios.get('/v1/catalogues', { params });
	return response.data.data as CataloguesResponse;
};

export const fetchCatalogue = async (uuid: string): Promise<CatalogueDetails> => {
	const response = await axios.get(`/v1/catalogues/${uuid}`);
	return response.data.data.catalogue;
};
