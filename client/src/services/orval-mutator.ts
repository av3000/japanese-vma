import type { AxiosError, AxiosRequestConfig } from 'axios';
import axiosInstance from '@/services/axios';

export const customInstance = async <T>(config: AxiosRequestConfig, options?: AxiosRequestConfig): Promise<T> => {
	const response = await axiosInstance({
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
