import axios, { AxiosInstance, InternalAxiosRequestConfig } from 'axios';
import { captureApiError, isSentryEnabled } from '@/lib/monitoring/sentry';

const axiosInstance: AxiosInstance = axios.create({
	baseURL: `${import.meta.env.VITE_API_URL}/api/`,
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

		const status = error.response?.status;
		const shouldCapture = isSentryEnabled && (status === undefined || status >= 500);

		if (shouldCapture) {
			captureApiError(error, {
				baseURL: error.config?.baseURL,
				method: error.config?.method,
				status,
				url: error.config?.url,
			});
		}

		return Promise.reject(error);
	},
);

export default axiosInstance;
