import { apiCall } from '@/services/api';
import { HttpMethod } from '@/shared/types';

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
