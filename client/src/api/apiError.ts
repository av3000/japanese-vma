import { isAxiosError } from 'axios';
import { isHttpValidationProblemDetails } from '@/helpers/isHttpValidationProblemDetails';

/**
 * What a failed API request means to the user: which kind of failure it was, one message to show,
 * and, for a 422, the messages per field. The v1 endpoints answer failures two ways:
 * `TypedResults` emits problem details (`{ title, status, detail }`), while a rejected form request
 * emits `{ message, errors }`. Every caller needs the same reading, so it lives here once.
 */
export type ApiError =
	| { kind: 'validation'; message: string; errors: Record<string, string[]> }
	| {
			kind: 'unauthenticated' | 'forbidden' | 'notFound' | 'rateLimited' | 'unreachable' | 'unknown';
			message: string;
	  };

type ApiErrorKind = Exclude<ApiError['kind'], 'validation'>;

export const API_ERROR_MESSAGES = {
	validation: 'Check the highlighted fields and try again.',
	unauthenticated: 'Your session has expired. Log in again to save your changes.',
	forbidden: "You don't have permission to change this.",
	notFound: 'This no longer exists. It may have been deleted.',
	rateLimited: 'Too many attempts. Wait a minute and try again.',
	unreachable: "We couldn't reach the server. Check your connection and try again.",
	unknown: 'Something went wrong. Please try again.',
} as const;

const KIND_BY_STATUS: Record<number, ApiErrorKind> = {
	401: 'unauthenticated',
	403: 'forbidden',
	404: 'notFound',
	429: 'rateLimited',
};

/**
 * Turns the error a failed request throws into what the user sees. The only server text that
 * reaches the screen is the 422 field messages, which are written for users; `title`, `detail` and
 * the axios message never do, because they name internals ("Access denied", "Catalogue not found").
 * The status comes from the HTTP response, since a proxy error page has no Problem Details at all.
 */
export const parseApiError = (error: unknown): ApiError => {
	const response = (error as { response?: { status?: number; data?: unknown } } | null)?.response;

	// An axios error with no response never reached the server; anything else without one is a bug.
	if (!response) {
		return isAxiosError(error)
			? { kind: 'unreachable', message: API_ERROR_MESSAGES.unreachable }
			: { kind: 'unknown', message: API_ERROR_MESSAGES.unknown };
	}

	const data = response.data;

	if (data && typeof data === 'object' && isHttpValidationProblemDetails(data)) {
		return { kind: 'validation', message: API_ERROR_MESSAGES.validation, errors: data.errors };
	}

	const status = response.status ?? (data as { status?: number } | undefined)?.status;
	const kind = (status && KIND_BY_STATUS[status]) || 'unknown';

	return { kind, message: API_ERROR_MESSAGES[kind] };
};
