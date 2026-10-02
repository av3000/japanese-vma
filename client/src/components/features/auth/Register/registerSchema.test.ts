import { describe, expect, it } from 'vitest';
import { PASSWORD_RULE_MESSAGES, passwordProblems, registerSchema } from './registerSchema';

const valid = {
	name: 'sora_kun-7',
	email: 'sora@example.com',
	password: 'Str0ng!Passw0rd',
	password_confirmation: 'Str0ng!Passw0rd',
};

const messagesFor = (values: Record<string, string>, field: string) => {
	const result = registerSchema.safeParse(values);

	return result.success ? [] : result.error.issues.filter((issue) => issue.path[0] === field).map((i) => i.message);
};

describe('passwordProblems', () => {
	it('accepts a password that meets every rule', () => {
		expect(passwordProblems('Str0ng!Passw0rd')).toEqual([]);
	});

	it('requires 8 characters', () => {
		expect(passwordProblems('Sh0rt!a')).toEqual([PASSWORD_RULE_MESSAGES.length]);
		expect(passwordProblems('Sh0rt!ab')).toEqual([]);
	});

	it('requires both cases', () => {
		expect(passwordProblems('lower0nly!')).toEqual([PASSWORD_RULE_MESSAGES.mixedCase]);
		expect(passwordProblems('UPPER0NLY!')).toEqual([PASSWORD_RULE_MESSAGES.mixedCase]);
	});

	it('requires a number', () => {
		expect(passwordProblems('NoNumbers!')).toEqual([PASSWORD_RULE_MESSAGES.number]);
	});

	it('requires a symbol, counting Unicode punctuation and spaces like Laravel does', () => {
		expect(passwordProblems('NoSymb0lsHere')).toEqual([PASSWORD_RULE_MESSAGES.symbol]);
		expect(passwordProblems('Has0Space Here')).toEqual([]);
		expect(passwordProblems('Has0Japanese。Dot')).toEqual([]);
		expect(passwordProblems('Has0Yen¥Sign')).toEqual([]);
	});

	it('lists every broken rule at once, in hint order', () => {
		expect(passwordProblems('!!!!')).toEqual([
			PASSWORD_RULE_MESSAGES.length,
			PASSWORD_RULE_MESSAGES.mixedCase,
			PASSWORD_RULE_MESSAGES.number,
		]);
	});
});

describe('registerSchema', () => {
	it('accepts valid details and trims name and email', () => {
		const result = registerSchema.parse({ ...valid, name: ' sora_kun-7 ', email: ' sora@example.com ' });

		expect(result.name).toBe('sora_kun-7');
		expect(result.email).toBe('sora@example.com');
	});

	it('uses the backend message for a username with disallowed characters', () => {
		expect(messagesFor({ ...valid, name: 'bad name!' }, 'name')).toEqual([
			'Username can only contain letters, numbers, underscores, and hyphens.',
		]);
	});

	it('caps username and email at 255 characters', () => {
		expect(messagesFor({ ...valid, name: 'a'.repeat(256) }, 'name')).toContain('Use at most 255 characters.');
		expect(messagesFor({ ...valid, email: `${'a'.repeat(250)}@x.com` }, 'email')).toContain(
			'Use at most 255 characters.',
		);
	});

	it('rejects a malformed email', () => {
		expect(messagesFor({ ...valid, email: 'not-an-email' }, 'email')).toEqual(['Enter a valid email address.']);
	});

	it('reports every broken password rule as its own issue', () => {
		expect(messagesFor({ ...valid, password: 'weak', password_confirmation: 'weak' }, 'password')).toEqual([
			PASSWORD_RULE_MESSAGES.length,
			PASSWORD_RULE_MESSAGES.mixedCase,
			PASSWORD_RULE_MESSAGES.number,
			PASSWORD_RULE_MESSAGES.symbol,
		]);
	});

	it('only asks for a value when username or email is empty', () => {
		const empty = { name: '', email: '', password: '', password_confirmation: '' };

		expect(messagesFor(empty, 'name')).toEqual(['Choose a username.']);
		expect(messagesFor(empty, 'email')).toEqual(['Enter your email.']);
		expect(messagesFor(empty, 'password_confirmation')).toEqual(['Repeat your password.']);
	});

	it('asks for a password rather than listing rules when it is empty', () => {
		expect(messagesFor({ ...valid, password: '', password_confirmation: '' }, 'password')).toEqual([
			'Choose a password.',
		]);
	});

	it('puts a confirmation mismatch on the confirmation field', () => {
		expect(
			messagesFor({ ...valid, password_confirmation: 'Str0ng!Passw0rd-typo' }, 'password_confirmation'),
		).toEqual(['The passwords do not match.']);
	});

	it('still checks the confirmation when other fields are invalid', () => {
		expect(
			messagesFor({ ...valid, name: 'bad name!', password_confirmation: 'different' }, 'password_confirmation'),
		).toEqual(['The passwords do not match.']);
	});
});
