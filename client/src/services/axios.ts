import axios, { AxiosInstance, InternalAxiosRequestConfig } from 'axios';

const axiosInstance: AxiosInstance = axios.create({
	baseURL: '/api',
	headers: {
		'Content-Type': 'application/json',
	},
});

axiosInstance.interceptors.request.use(
	(config: InternalAxiosRequestConfig) => {
		const token = localStorage.getItem('token');

		if (token && config.headers) {
			config.headers.Authorization = `Bearer ${token}`;
		}

		return config;
	},
	(error) => {
		return Promise.reject(error);
	},
);

axiosInstance.interceptors.response.use(
	(response) => response,
	(error) => {
		if (error.response?.status === 401) {
			const path = error.config?.url || '';

			const publicEndpoints = ['/v1/login', '/v1/register'];
			const isPublicEndpoint = publicEndpoints.some((endpoint) => path.includes(endpoint));

			if (!isPublicEndpoint) {
				window.dispatchEvent(new CustomEvent('auth:unauthorized'));
			}
		}

		return Promise.reject(error);
	},
);

export default axiosInstance;
