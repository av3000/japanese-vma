import { z } from 'zod';

/**
 * Client-side copy of the static rules in
 * `processor-api/app/Http/v1/Auth/Requests/RegisterRequest.php`, which stays the source of truth.
 * It gives instant feedback; it does not replace the server.
 *
 * Deliberately not mirrored, because only the server can answer them: unique name and email, the
 * `email:rfc,dns` domain check, and `Password::uncompromised()`. Those come back as 422 field errors.
 * `Password::letters()` is not mirrored either: `mixedCase()` already implies it, and listing both
 * would show two messages for one missing letter.
 */

export const USERNAME_PATTERN = /^[a-zA-Z0-9_-]+$/;

export const PASSWORD_MIN_LENGTH = 8;

/** Laravel's `Password::symbols()` test: any separator, symbol or punctuation character. */
const SYMBOL_PATTERN = /\p{Z}|\p{S}|\p{P}/u;

export const PASSWORD_RULE_MESSAGES = {
	length: `Use at least ${PASSWORD_MIN_LENGTH} characters.`,
	mixedCase: 'Include both an uppercase and a lowercase letter.',
	number: 'Include at least one number.',
	symbol: 'Include at least one symbol, such as ! or #.',
} as const;

/** Every password rule the value breaks, in the order the hint lists them. */
export const passwordProblems = (password: string): string[] => {
	const problems: string[] = [];

	if (password.length < PASSWORD_MIN_LENGTH) problems.push(PASSWORD_RULE_MESSAGES.length);
	if (!/\p{Ll}/u.test(password) || !/\p{Lu}/u.test(password)) problems.push(PASSWORD_RULE_MESSAGES.mixedCase);
	if (!/\p{N}/u.test(password)) problems.push(PASSWORD_RULE_MESSAGES.number);
	if (!SYMBOL_PATTERN.test(password)) problems.push(PASSWORD_RULE_MESSAGES.symbol);

	return problems;
};

// The form reports every broken rule at once, so format checks skip an empty value: an empty
// field should only ask for a value, not also call it malformed.
const isEmail = (value: string) => z.string().email().safeParse(value).success;

export const registerSchema = z
	.object({
		name: z
			.string()
			.trim()
			.min(1, 'Choose a username.')
			.max(255, 'Use at most 255 characters.')
			.refine(
				(name) => name === '' || USERNAME_PATTERN.test(name),
				'Username can only contain letters, numbers, underscores, and hyphens.',
			),
		email: z
			.string()
			.trim()
			.min(1, 'Enter your email.')
			.max(255, 'Use at most 255 characters.')
			.refine((email) => email === '' || isEmail(email), 'Enter a valid email address.'),
		password: z.string().superRefine((password, context) => {
			if (password === '') {
				context.addIssue({ code: z.ZodIssueCode.custom, message: 'Choose a password.' });
				return;
			}

			for (const message of passwordProblems(password)) {
				context.addIssue({ code: z.ZodIssueCode.custom, message });
			}
		}),
		password_confirmation: z.string().min(1, 'Repeat your password.'),
	})
	.superRefine((values, context) => {
		if (values.password_confirmation !== '' && values.password_confirmation !== values.password) {
			context.addIssue({
				code: z.ZodIssueCode.custom,
				path: ['password_confirmation'],
				message: 'The passwords do not match.',
			});
		}
	});

export type RegisterValues = z.infer<typeof registerSchema>;
