import type { AxiosError, AxiosRequestConfig } from 'axios';
import axiosInstance from '@/services/axios';

const ORVAL_API_BASE_URL = `${import.meta.env.VITE_API_URL}/api/v1/`;

export const customInstance = async <T>(config: AxiosRequestConfig, options?: AxiosRequestConfig): Promise<T> => {
	const response = await axiosInstance({
		baseURL: ORVAL_API_BASE_URL,
		...config,
		...options,
		headers: {
			...config.headers,
			...options?.headers,
		},
	});

	return response.data as T;
};

export type ErrorType<Error> = AxiosError<Error>;
export type BodyType<Body> = Body;
