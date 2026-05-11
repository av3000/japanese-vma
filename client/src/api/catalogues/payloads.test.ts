import { describe, expect, it } from 'vitest';
import { buildCreateCataloguePayload, buildUpdateCataloguePayload } from './payloads';

describe('catalogue form payload mapping', () => {
	it('maps create form values onto the generated store request shape', () => {
		expect(
			buildCreateCataloguePayload({
				title: '  Tokyo Words  ',
				type: 7,
				publicity: true,
				tags: ['tokyo', '#study'],
			}),
		).toEqual({
			title: 'Tokyo Words',
			type: 7,
			publicity: true,
			tags: '#tokyo #study',
		});
	});

	it('only sends dirty edit fields in the generated update request shape', () => {
		expect(
			buildUpdateCataloguePayload(
				{
					title: '  Updated  ',
					type: 8,
					publicity: false,
					tags: ['sentence', 'review'],
				},
				['title', 'tags'],
			),
		).toEqual({
			title: 'Updated',
			tags: '#sentence #review',
		});
	});
});
