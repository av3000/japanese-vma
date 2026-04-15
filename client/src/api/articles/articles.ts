import axios from '@/services/axios';

// TODO: refactor to use auto generated endpoint instead, when v1 catalogue service method is ready
export const fetchArticleSavedLists = async (id: string) => {
	const response = await axios.post(`user/lists/contain`, { elementId: id });
	return response.data.lists || [];
};

// TODO: refactor to use auto generated endpoint instead, when v1 article service method is ready
export const setArticleStatus = async (id: string, status: number) => {
	return axios.post(`article/${id}/setstatus`, { status });
};
