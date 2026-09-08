import { isHttpValidationProblemDetails } from '@/helpers/isHttpValidationProblemDetails';

/**
 * The v1 write endpoints answer failures two ways: `TypedResults` emits problem details
 * (`{ title, status, detail }`), while a rejected form request emits `{ message, errors }`.
 * Every write seam needs the same discrimination, so the reader lives here rather than being
 * re-derived per resource.
 */
export type WriteFailure =
	| { kind: 'validation'; message: string; errors: Record<string, string[]> }
	| { kind: 'unauthenticated' | 'forbidden' | 'notFound' | 'unknown'; message: string };

type WriteFailureKind = Exclude<WriteFailure['kind'], 'validation'>;

const FAILURE_KIND_BY_STATUS: Record<number, WriteFailureKind> = {
	401: 'unauthenticated',
	403: 'forbidden',
	404: 'notFound',
};

export const readWriteFailure = (error: unknown, genericMessage: string): WriteFailure => {
	const data = (error as { response?: { data?: unknown } })?.response?.data;

	if (data && isHttpValidationProblemDetails(data)) {
		return {
			kind: 'validation',
			message: data.title ?? 'Validation failed',
			errors: data.errors,
		};
	}

	const problem = data as { status?: number; title?: string } | undefined;
	const kind = problem?.status ? (FAILURE_KIND_BY_STATUS[problem.status] ?? 'unknown') : 'unknown';

	return {
		kind,
		message: kind === 'unknown' ? genericMessage : (problem?.title ?? genericMessage),
	};
};
