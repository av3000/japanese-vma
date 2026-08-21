import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { describe, expect, it } from 'vitest';

describe('lazy route loading coverage', () => {
	const source = readFileSync(fileURLToPath(new URL('./routes.tsx', import.meta.url)), 'utf8');

	it('constructs all 25 lazy page modules through createLazyRoute', () => {
		expect(source.match(/createLazyRoute\s*\(/g)).toHaveLength(25);
	});

	it('does not permit a direct lazy page or the nullable SuspenseWrapper', () => {
		expect(source).not.toMatch(/\blazy\s*\(/);
		expect(source).not.toContain('SuspenseWrapper');
		expect(source).not.toContain('fallback = null');
	});

	it('keeps Article Details and Kanjis behind the factory seam', () => {
		expect(source).toMatch(/ArticleDetailsPage\s*=\s*createLazyRoute/);
		expect(source).toMatch(/KanjisPage\s*=\s*createLazyRoute/);
	});
});
