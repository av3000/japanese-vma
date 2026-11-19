import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import type { TranslationKey } from './keys';
import { createTranslate, enSnapshot, resetMissingKeyWarnings } from './translate';

/** A key the types reject, for the runtime path a key that arrives from outside takes. */
const unknownKey = 'account.not_a_key' as TranslationKey;

describe('createTranslate', () => {
	let warn: ReturnType<typeof vi.spyOn>;

	beforeEach(() => {
		resetMissingKeyWarnings();
		warn = vi.spyOn(console, 'warn').mockImplementation(() => undefined);
	});

	afterEach(() => {
		vi.unstubAllEnvs();
		warn.mockRestore();
	});

	it('reads the bundle first, then the English snapshot', () => {
		const t = createTranslate({ 'account.log_in': 'Log ind' });

		expect(t('account.log_in')).toBe('Log ind');
		// Not in this bundle, so English fills in: a label is never blank.
		expect(t('account.sign_up')).toBe(enSnapshot['account.sign_up']);
	});

	it('treats an empty bundle value as missing', () => {
		expect(createTranslate({ 'account.log_in': '' })('account.log_in')).toBe(enSnapshot['account.log_in']);
	});

	it('returns the key when neither the bundle nor the snapshot has it', () => {
		expect(createTranslate({})(unknownKey)).toBe('account.not_a_key');
	});

	it('does not read Object.prototype for a key that arrives at runtime', () => {
		const t = createTranslate({});

		expect(t('constructor' as TranslationKey)).toBe('constructor');
		expect(t('toString' as TranslationKey)).toBe('toString');
	});

	it('warns once per missing key in a development build', () => {
		vi.stubEnv('DEV', true);
		const t = createTranslate({});

		t(unknownKey);
		t(unknownKey);
		t('account.log_in');

		expect(warn).toHaveBeenCalledTimes(1);
		expect(warn.mock.calls[0]?.[0]).toContain('account.not_a_key');
	});

	it('does not warn in a production build', () => {
		vi.stubEnv('DEV', false);

		expect(createTranslate({})(unknownKey)).toBe('account.not_a_key');
		expect(warn).not.toHaveBeenCalled();
	});

	it('accepts only keys of the English snapshot', () => {
		const t = createTranslate({});

		expect(t('account.log_in')).toBeTruthy();

		// A typo in a key literal is a compile error, so `npm run typecheck` fails before it ships.
		// @ts-expect-error 'account.log_inn' is not a TranslationKey
		expect(t('account.log_inn')).toBe('account.log_inn');
	});
});
