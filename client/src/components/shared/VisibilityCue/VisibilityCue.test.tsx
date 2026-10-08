import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import type { PublicityStatus } from '@/api/generated/model/publicityStatus';
import { PUBLICITY } from '@/api/publicity';
import { VisibilityCue, visibilityCue } from './';

describe('visibilityCue', () => {
	it('maps 1 to public', () => {
		expect(visibilityCue(PUBLICITY.PUBLIC)).toMatchObject({
			visibility: 'public',
			label: 'Public',
			icon: 'lockOpenSolid',
		});
	});

	it.each([PUBLICITY.PRIVATE, null, undefined, 7 as PublicityStatus])('reads %j as private', (publicity) => {
		expect(visibilityCue(publicity)).toMatchObject({ visibility: 'private', label: 'Private', icon: 'lockSolid' });
	});
});

describe('VisibilityCue', () => {
	it('carries the meaning as text next to a decorative icon', () => {
		const html = renderToStaticMarkup(<VisibilityCue publicity={PUBLICITY.PRIVATE} />);

		expect(html).toContain('data-visibility="private"');
		expect(html).toMatch(/aria-hidden="true".*Private<\/span>$/);
	});
});
