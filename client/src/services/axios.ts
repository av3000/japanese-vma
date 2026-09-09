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

/**
 * Endpoints that answer 401 as a normal outcome and whose caller renders the failure itself:
 * bad credentials on sign-in, and a token the server has already forgotten on sign-out. Escalating
 * any of these to `auth:unauthorized` would replace the caller's message with a spurious
 * "session expired" bounce.
 *
 * Matching on the final path segment keeps this working regardless of how much version prefix the
 * caller carries: the generated clients send a bare path against a versioned base URL, while legacy
 * callers still put the version in the path itself.
 */
const SELF_HANDLED_401_ENDPOINTS = ['login', 'register', 'logout'];

const isSelfHandled401 = (url: string) => {
	const [path] = url.split('?');
	const segment = path.split('/').filter(Boolean).pop() ?? '';

	return SELF_HANDLED_401_ENDPOINTS.includes(segment);
};

axiosInstance.interceptors.response.use(
	(response) => response,
	(error) => {
		if (error.response?.status === 401) {
			if (!isSelfHandled401(error.config?.url || '')) {
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
