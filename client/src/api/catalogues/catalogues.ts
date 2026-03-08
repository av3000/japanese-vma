import axios from '@/services/axios';

export interface CatalogueHashtag {
	id: string;
	content: string;
}

export interface CatalogueEngagementStats {
	likes_count: number;
	views_count: number;
	downloads_count: number;
	comments_count: number;
}

export interface CatalogueOwner {
	id: number;
	name: string;
	uuid: string;
}

export interface Catalogue {
	id: number;
	uuid: string;
	type: number;
	type_label: string;
	title: string;
	description: string | null;
	publicity: number;
	owner: CatalogueOwner;
	items_count: number;
	hashtags: CatalogueHashtag[];
	engagement: CatalogueEngagementStats | null;
	created_at: string;
	updated_at: string;
}

export interface CatalogueDetails {
	id: number;
	uuid: string;
	type: number;
	type_label: string;
	title: string;
	description: string | null;
	publicity: number;
	owner: CatalogueOwner;
	items_count: number;
	hashtags: CatalogueHashtag[];
	engagement: CatalogueEngagementStats | null;
	items: unknown[];
	created_at: string;
	updated_at: string;
}

export interface CataloguesResponse {
	items: Catalogue[];
	pagination: {
		page: number;
		per_page: number;
		total: number;
		last_page: number;
		has_more: boolean;
	};
}

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
