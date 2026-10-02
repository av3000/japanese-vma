import type { LoginRequest } from '@/api/generated/model/loginRequest';
import type { RegisterRequest } from '@/api/generated/model/registerRequest';
import { isHttpValidationProblemDetails } from '@/helpers/isHttpValidationProblemDetails';

export type AuthField = keyof RegisterRequest | keyof LoginRequest;

export interface AuthFailure {
	/** For the general alert at the top of the card. `null` when every message belongs to a field. */
	message: string | null;
	fieldErrors: Partial<Record<AuthField, string[]>>;
}

const AUTH_FIELDS: readonly AuthField[] = ['name', 'email', 'password', 'password_confirmation'];

export const AUTH_FAILURE_MESSAGES = {
	invalidCredentials: 'Email or password is incorrect.',
	checkForm: 'Check the form and try again.',
	rateLimited: 'Too many attempts. Wait a minute and try again.',
	unreachable: "We couldn't reach the server. Check your connection and try again.",
	unknown: 'Something went wrong on our side. Try again in a moment.',
} as const;

const isAuthField = (key: string): key is AuthField => (AUTH_FIELDS as readonly string[]).includes(key);

const fromValidation = (errors: Record<string, string[]>): AuthFailure => {
	const fieldErrors: AuthFailure['fieldErrors'] = {};
	const unplaced: string[] = [];

	for (const [key, messages] of Object.entries(errors)) {
		if (isAuthField(key)) {
			fieldErrors[key] = messages;
		} else {
			unplaced.push(...messages);
		}
	}

	const placedAny = Object.keys(fieldErrors).length > 0;
	const message = unplaced.length > 0 ? unplaced.join(' ') : placedAny ? null : AUTH_FAILURE_MESSAGES.checkForm;

	return { message, fieldErrors };
};

/**
 * Turns a rejected login or register call into what the auth card shows. Every v1 failure is a
 * Problem Details body, so `response.data.message` never exists; reading it is what used to put
 * "Request failed with status code 401" on screen. The status comes from the HTTP response rather
 * than the body, because a proxy error page has no Problem Details at all.
 *
 * The only server text that reaches the user is the 422 field messages, which are written for
 * users. `title`, `detail` and the axios message never do.
 */
export const readAuthFailure = (error: unknown): AuthFailure => {
	const response = (error as { response?: { status?: number; data?: unknown } } | null)?.response;

	if (!response) {
		return { message: AUTH_FAILURE_MESSAGES.unreachable, fieldErrors: {} };
	}

	if (response.status === 422 && response.data && isHttpValidationProblemDetails(response.data)) {
		return fromValidation(response.data.errors);
	}

	if (response.status === 401) {
		return { message: AUTH_FAILURE_MESSAGES.invalidCredentials, fieldErrors: {} };
	}

	if (response.status === 429) {
		return { message: AUTH_FAILURE_MESSAGES.rateLimited, fieldErrors: {} };
	}

	return { message: AUTH_FAILURE_MESSAGES.unknown, fieldErrors: {} };
};
