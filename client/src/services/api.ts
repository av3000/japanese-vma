import { AxiosRequestConfig, AxiosResponse } from 'axios';
import { HttpMethod } from '@/shared/types';
import axiosInstance from './axios';

interface ApiCallOptions {
	method: HttpMethod;
	path: string;
	data?: any;
	config?: AxiosRequestConfig;
}

export async function apiCall<T = any>({ method, path, data = {}, config = {} }: ApiCallOptions): Promise<T> {
	console.log('Request:', { method, path, data });

	try {
		const requestConfig: AxiosRequestConfig =
			method === HttpMethod.GET && data ? { ...config, params: data } : config;

		const response: AxiosResponse<T> = await (method === HttpMethod.GET || method === HttpMethod.DELETE
			? axiosInstance[method](path, requestConfig)
			: axiosInstance[method](path, data, requestConfig));

		console.log('Response from', path, ':', response.data);
		return response.data;
	} catch (error) {
		throw createApiError(error);
	}
}

function createApiError(error: any): Error {
	console.error('API Call Error:', error);

	if (error.response) {
		const errorMessage =
			error.response.data?.message ||
			error.response.data?.error ||
			(typeof error.response.data === 'string' ? error.response.data : JSON.stringify(error.response.data));

		const err = new Error(errorMessage);
		(err as any).response = error.response; // Preserve response for components
		(err as any).status = error.response.status;
		return err;
	} else if (error.request) {
		return new Error('No response received from server');
	} else {
		return new Error(error.message || 'Error making request');
	}
}
