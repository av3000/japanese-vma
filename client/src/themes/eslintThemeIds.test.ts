import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { describe, expect, it } from 'vitest';
import { THEME_IDS } from './manifest';

/** ESLint forbids comparing these ids outside src/themes/; its list must match the manifest. */
describe('eslint.config.mjs theme-name rule', () => {
	it('lists exactly the manifest’s theme ids', () => {
		const config = readFileSync(resolve(__dirname, '../../eslint.config.mjs'), 'utf8');
		const listed = /const THEME_IDS = (\[[^\]]*\]);/.exec(config)?.[1];

		expect(listed && JSON.parse(listed)).toEqual([...THEME_IDS]);
	});
});
