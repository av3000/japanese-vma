import axios from '@/services/axios';

export const fetchArticleSavedLists = async (id: string) => {
	const response = await axios.post(`user/lists/contain`, { elementId: id });
	return response.data.lists || [];
};

export const setArticleStatus = async (id: string, status: number) => {
	return axios.post(`article/${id}/setstatus`, { status });
};
