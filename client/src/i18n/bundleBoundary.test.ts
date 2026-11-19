import { describe, expect, it } from 'vitest';

/** Every source file, as text. The generated client is the only code allowed to name the endpoint. */
const sources = import.meta.glob<string>(['/src/**/*.{ts,tsx}', '!/src/api/generated/**'], {
	query: '?raw',
	import: 'default',
	eager: true,
});

const PROVIDER = '/src/providers/contexts/translation-provider.tsx';

/** What a call to the bundle endpoint looks like: the generated hook or function, or the URL itself. */
const bundleCall = /\b(?:useTranslationShow|translationShow|getTranslationShow\w*)\b|['"`]\/translations\//;

describe('the translation bundle endpoint', () => {
	it('is called only by the TranslationProvider (FR-12)', () => {
		const callers = Object.entries(sources)
			.filter(([path]) => !path.includes('.test.') && !path.includes('.stories.'))
			.filter(([, source]) => bundleCall.test(source))
			.map(([path]) => path);

		expect(callers).toEqual([PROVIDER]);
	});

	it('sees the source files at all', () => {
		expect(Object.keys(sources)).toContain(PROVIDER);
	});
});
