import { LastOperationEvent } from '@/api/last-operations/last-operations';
import axios from '@/services/axios';

interface Hashtag {
	id: string;
	content: string;
}

export interface ArticleDetails {
	id: number;
	// TODO: fix in backend to have proper uuid
	uid: string;
	title_jp: string;
	title_en: string;
	content_jp: string;
	content_en: string;
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
	engagement: { likes_count: number; views_count: number; downloads_count: number; is_liked_by_viewer: boolean };
	kanjis: any;
	words: any;
	processing_status: LastOperationEvent | null;
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
	processing_status: LastOperationEvent | null;
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

export interface CreateArticlePayload {
	title_jp: string;
	title_en?: string | null;
	content_jp: string;
	content_en?: string | null;
	source_link: string;
	publicity: boolean | number;
	tags?: string[];
	attach?: number;
}

export interface CreateArticleResponse {
	uuid: string;
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

export const fetchArticle = async (uuid: string): Promise<ArticleDetails> => {
	const response = await axios.get(`v1/articles/${uuid}`);
	return response.data.data.article;
};

export const fetchArticleSavedLists = async (id: string) => {
	const response = await axios.post(`user/lists/contain`, { elementId: id });
	return response.data.lists || [];
};

export const setArticleStatus = async (id: string, status: number) => {
	return axios.post(`article/${id}/setstatus`, { status });
};

export const deleteArticle = async (id: number) => {
	return axios.delete(`article/${id}`);
};

export const createArticle = async (payload: CreateArticlePayload): Promise<CreateArticleResponse> => {
	const response = await axios.post('/v1/articles', payload);
	return { uuid: response.data.data.uuid ?? response.data };
};
