import { isAxiosError } from 'axios';
import { isHttpValidationProblemDetails } from '@/helpers/isHttpValidationProblemDetails';

/**
 * The v1 write endpoints answer failures two ways: `TypedResults` emits problem details
 * (`{ title, status, detail }`), while a rejected form request emits `{ message, errors }`.
 * Every write seam needs the same discrimination, so the reader lives here rather than being
 * re-derived per resource.
 */
export type WriteFailure =
	| { kind: 'validation'; message: string; errors: Record<string, string[]> }
	| {
			kind: 'unauthenticated' | 'forbidden' | 'notFound' | 'rateLimited' | 'unreachable' | 'unknown';
			message: string;
	  };

type WriteFailureKind = Exclude<WriteFailure['kind'], 'validation'>;

export const WRITE_FAILURE_MESSAGES = {
	validation: 'Check the highlighted fields and try again.',
	unauthenticated: 'Your session has expired. Log in again to save your changes.',
	forbidden: "You don't have permission to change this.",
	notFound: 'This no longer exists. It may have been deleted.',
	rateLimited: 'Too many attempts. Wait a minute and try again.',
	unreachable: "We couldn't reach the server. Check your connection and try again.",
} as const;

const FAILURE_KIND_BY_STATUS: Record<number, WriteFailureKind> = {
	401: 'unauthenticated',
	403: 'forbidden',
	404: 'notFound',
	429: 'rateLimited',
};

/**
 * Turns a rejected write into what a form or row shows. The only server text that reaches the user
 * is the 422 field messages, which are written for users; `title`, `detail` and the axios message
 * never do, because they name internals ("Access denied", "Catalogue not found"). The status comes
 * from the HTTP response, since a proxy error page has no Problem Details at all.
 */
export const readWriteFailure = (error: unknown, genericMessage: string): WriteFailure => {
	const response = (error as { response?: { status?: number; data?: unknown } } | null)?.response;

	// An axios error with no response never reached the server; anything else without one is a bug.
	if (!response) {
		return isAxiosError(error)
			? { kind: 'unreachable', message: WRITE_FAILURE_MESSAGES.unreachable }
			: { kind: 'unknown', message: genericMessage };
	}

	const data = response.data;

	if (data && typeof data === 'object' && isHttpValidationProblemDetails(data)) {
		return { kind: 'validation', message: WRITE_FAILURE_MESSAGES.validation, errors: data.errors };
	}

	const status = response.status ?? (data as { status?: number } | undefined)?.status;
	const kind = (status && FAILURE_KIND_BY_STATUS[status]) || 'unknown';

	return { kind, message: kind === 'unknown' ? genericMessage : WRITE_FAILURE_MESSAGES[kind] };
};
