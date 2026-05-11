import { describe, expect, it } from 'vitest';
import { buildCatalogueFormSchema } from './catalogueFormSchema';

describe('buildCatalogueFormSchema', () => {
	it('accepts the supported catalogue create/edit fields', () => {
		const schema = buildCatalogueFormSchema();

		expect(
			schema.parse({
				title: 'Tokyo Words',
				type: 7,
				publicity: true,
				tags: ['tokyo', 'study'],
			}),
		).toEqual({
			title: 'Tokyo Words',
			type: 7,
			publicity: true,
			tags: ['tokyo', 'study'],
		});
	});

	it('rejects short titles and unsupported list types', () => {
		const schema = buildCatalogueFormSchema();
		const result = schema.safeParse({
			title: 'A',
			type: 4,
			publicity: false,
			tags: [],
		});

		expect(result.success).toBe(false);
		if (result.success) {
			throw new Error('Expected validation to fail');
		}

		expect(result.error.flatten().fieldErrors.title?.[0]).toContain('at least 2 characters');
		expect(result.error.flatten().fieldErrors.type?.[0]).toContain('supported');
	});
});
