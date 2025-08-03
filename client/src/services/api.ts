import axios, { AxiosRequestConfig, AxiosResponse } from 'axios';

type HttpMethod = 'GET' | 'POST' | 'PUT' | 'PATCH' | 'DELETE';

interface ApiCallOptions {
	method: HttpMethod;
	path: string;
	data?: any;
	config?: AxiosRequestConfig;
}

export function setTokenHeader(token: string | null): void {
	if (token) {
		axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
	} else {
		delete axios.defaults.headers.common['Authorization'];
	}
}

export async function apiCall<T = any>({ method, path, data, config = {} }: ApiCallOptions): Promise<T> {
	const normalizedMethod = method.toLowerCase();
	console.log(`Making ${normalizedMethod} request to ${path}`, data);

	try {
		// Handle GET requests with query parameters
		const requestConfig: AxiosRequestConfig =
			normalizedMethod === 'get' && data ? { ...config, params: data } : config;

		// Make the request based on method type
		const response: AxiosResponse<T> = await (normalizedMethod === 'get' || normalizedMethod === 'delete'
			? axios[normalizedMethod](path, requestConfig)
			: axios[normalizedMethod](path, data, requestConfig));

		console.log(`Response from ${path}:`, response.data);
		return response.data;
	} catch (error) {
		throw createApiError(error);
	}
}

function createApiError(error: any): Error {
	console.error('API Call Error:', error);

	if (error.response) {
		// Server responded with error status
		const errorMessage =
			error.response.data?.error ||
			(typeof error.response.data === 'string' ? error.response.data : JSON.stringify(error.response.data));
		return new Error(errorMessage);
	} else if (error.request) {
		// Request made but no response received
		return new Error('No response received from server');
	} else {
		// Something else happened
		return new Error(error.message || 'Error making request');
	}
}
