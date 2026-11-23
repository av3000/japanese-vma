import { describe, expect, it } from 'vitest';
import { resolveReturnTo } from './returnTo';

describe('resolveReturnTo', () => {
	it('returns to the page PrivateRoute bounced from, with its query and hash', () => {
		expect(resolveReturnTo({ from: { pathname: '/dashboard', search: '?tab=catalogues', hash: '#top' } })).toBe(
			'/dashboard?tab=catalogues#top',
		);
	});

	it('goes home without state', () => {
		expect(resolveReturnTo(undefined)).toBe('/');
		expect(resolveReturnTo(null)).toBe('/');
		expect(resolveReturnTo({})).toBe('/');
	});

	it('never returns to an auth page', () => {
		expect(resolveReturnTo({ from: { pathname: '/login', search: '', hash: '' } })).toBe('/');
		expect(resolveReturnTo({ from: { pathname: '/register', search: '', hash: '' } })).toBe('/');
	});

	it('refuses anything that is not an in-app path', () => {
		expect(resolveReturnTo({ from: { pathname: '//evil.example/x', search: '', hash: '' } })).toBe('/');
		expect(resolveReturnTo({ from: { pathname: 'https://evil.example', search: '', hash: '' } })).toBe('/');
		expect(resolveReturnTo({ from: { pathname: 42 } })).toBe('/');
	});
});
