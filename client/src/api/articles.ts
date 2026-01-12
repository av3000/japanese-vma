import axios from '@/services/axios';

export const lastOperationStatuses = ['pending', 'processing', 'completed', 'failed'] as const;
export type LastOperationStatus = (typeof lastOperationStatuses)[number];

interface LastOperationEvent {
	id: number;
	type: string;
	status: LastOperationStatus;
	metadata: Record<string, any>;
}

interface Hashtag {
	id: string;
	content: string;
}

export interface Article {
	id: number;
	uuid: string;
	title_jp: string;
	content_preview_jp: string;
	hashtags: Hashtag[];
	jlpt_levels: {
		n1: number;
		n2: number;
		n3: number;
		n4: number;
		n5: number;
		uncommon: number;
	};
	source_link: string;
	publicity: number;
	status: number;
	author: {
		id: number;
		name: string;
	};
	created_at: string;
	updated_at: string;
	engagement: { stats: any };
	kanjis: any;
	processing_status?: LastOperationEvent;
}

export interface ArticlesResponse {
	items: Article[];
	pagination: {
		page: number;
		last_page: number;
		has_more: boolean;
		total: number;
	};
}

// TODO: explore option to use Orval generated data contracts
export const fetchArticles = async (filters: Record<string, any>, pageParam: number) => {
	const params = { ...filters, page: pageParam };
	const url = `/v1/articles`;

	try {
		const response = await axios.get(url, { params });
		return response.data.data;
	} catch (error) {
		console.error('Axios failed:', error);
		throw error;
	}
};

export const fetchArticle = async (uuid: string) => {
	const response = await axios.get(`v1/articles/${uuid}`);
	return response.data.article;
};

export const fetchArticleLikeStatus = async (id: string) => {
	const response = await axios.post(`article/${id}/checklike`);
	return response.data;
};

export const fetchArticleSavedLists = async (id: string) => {
	const response = await axios.post(`user/lists/contain`, { elementId: id });
	return response.data.lists || [];
};

// TODO: should use request object parameters instead of named parameter:
// ex: params: {like: boolean}
// This change requires backend logic migration and liking implementation in V1 routes
export const toggleArticleLike = async (id: number, isLiked: boolean) => {
	const endpoint = isLiked ? 'unlike' : 'like';
	return axios.post(`article/${id}/${endpoint}`);
};

export const setArticleStatus = async (id: string, status: number) => {
	return axios.post(`article/${id}/setstatus`, { status });
};

export const deleteArticle = async (id: number) => {
	return axios.delete(`article/${id}`);
};
