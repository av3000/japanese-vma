import axios from '@/services/axios';
import { apiCall } from '@/services/api';
import { HttpMethod } from '@/shared/types';
import type { CustomCatalogueType } from '@/shared/constants/catalogues';

export interface CatalogueHashtag {
	id: number;
	content: string;
}

export interface CatalogueEngagementStats {
	likes_count: number;
	downloads_count: number;
	views_count: number;
	comments_count: number;
}

export interface CatalogueOwner {
	id: number;
	uuid: string;
	name: string;
}

export interface CataloguePagination {
	page: number;
	per_page: number;
	total: number;
	last_page: number;
	has_more: boolean;
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

export interface CatalogueDetails extends Catalogue {
	items: unknown[];
}

export interface CataloguesResponse {
	items: Catalogue[];
	pagination: CataloguePagination;
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

export interface CreateCataloguePayload {
	title: string;
	type: CustomCatalogueType;
	description?: string | null;
	publicity?: boolean;
	tags?: string;
}

export interface UpdateCataloguePayload {
	title?: string;
	type?: CustomCatalogueType;
	publicity?: boolean;
	tags?: string;
}

interface CatalogueUuidResponse {
	uuid: string;
}

interface LegacyCatalogueResponse {
	list: {
		id: number;
		uuid?: string;
		title: string;
	};
}

interface LegacyLikeResponse {
	isLiked: boolean;
}

export interface LegacyCatalogueIdentity {
	id: number;
	uuid: string;
	title: string;
}

export const stringifyCatalogueTags = (tags: string[]) => {
	const uniqueTags = Array.from(
		new Set(
			tags
				.map((tag) => tag.trim())
				.filter(Boolean)
				.map((tag) => (tag.startsWith('#') ? tag : `#${tag}`)),
		),
	);

	return uniqueTags.join(' ');
};

export const fetchCatalogues = async (filters: FetchCataloguesFilters = {}, pageParam = 1) => {
	const params = { ...filters, page: pageParam };
	const response = await axios.get('/v1/catalogues', { params });
	return response.data.data as CataloguesResponse;
};

export const fetchCatalogue = async (uuid: string): Promise<CatalogueDetails> => {
	const response = await axios.get(`/v1/catalogues/${uuid}`);
	return response.data.data.catalogue as CatalogueDetails;
};

export const createCatalogue = async (payload: CreateCataloguePayload): Promise<CatalogueUuidResponse> => {
	const response = await axios.post('/v1/catalogues', payload);
	return response.data.data as CatalogueUuidResponse;
};

export const updateCatalogue = async (uuid: string, payload: UpdateCataloguePayload): Promise<Catalogue> => {
	const response = await axios.put(`/v1/catalogues/${uuid}`, payload);
	return response.data.data as Catalogue;
};

export const resolveLegacyCatalogueIdentity = async (
	identifier: string | number,
): Promise<LegacyCatalogueIdentity> => {
	const response = await apiCall<LegacyCatalogueResponse>({
		method: HttpMethod.GET,
		path: `/list/${identifier}`,
	});

	const uuid = response.list.uuid;
	if (!uuid) {
		throw new Error('Legacy list response did not include a catalogue UUID');
	}

	return {
		id: response.list.id,
		uuid,
		title: response.list.title,
	};
};

export const checkLegacyCatalogueLike = async (catalogueId: number) => {
	const response = await apiCall<LegacyLikeResponse>({
		method: HttpMethod.POST,
		path: `/list/${catalogueId}/checklike`,
	});

	return response.isLiked;
};

export const setLegacyCatalogueLike = async (catalogueId: number, shouldLike: boolean) => {
	await apiCall({
		method: HttpMethod.POST,
		path: `/list/${catalogueId}/${shouldLike ? 'like' : 'unlike'}`,
	});
};

export const deleteLegacyCatalogue = async (catalogueId: number) => {
	await apiCall({
		method: HttpMethod.DELETE,
		path: `/list/${catalogueId}`,
	});
};

export const removeLegacyCatalogueItem = async (catalogueId: number, elementId: number) => {
	await apiCall({
		method: HttpMethod.POST,
		path: '/user/list/removeitemwhileaway',
		data: {
			listId: catalogueId,
			elementId,
		},
	});
};

export const getLegacyCataloguePdfEndpoint = (catalogueType: number) => {
	switch (catalogueType) {
		case 5:
			return 'radicals-pdf';
		case 6:
			return 'kanjis-pdf';
		case 7:
			return 'words-pdf';
		case 8:
			return 'sentences-pdf';
		default:
			return null;
	}
};

export const downloadLegacyCataloguePdf = async (catalogueId: number, catalogueType: number) => {
	const endpoint = getLegacyCataloguePdfEndpoint(catalogueType);
	if (!endpoint) {
		throw new Error('PDF export is not available for this catalogue type');
	}

	return apiCall<BlobPart>({
		method: HttpMethod.GET,
		path: `/list/${catalogueId}/${endpoint}`,
		config: {
			responseType: 'blob',
		},
	});
};
