import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { VisibilityCue, visibilityCue } from './';

describe('visibilityCue', () => {
	it('maps 1 to public', () => {
		expect(visibilityCue(1)).toMatchObject({ visibility: 'public', label: 'Public', icon: 'lockOpenSolid' });
		expect(visibilityCue(true).label).toBe('Public');
	});

	it.each([0, false, null, undefined, 2])('reads %j as private', (publicity) => {
		expect(visibilityCue(publicity)).toMatchObject({ visibility: 'private', label: 'Private', icon: 'lockSolid' });
	});
});

describe('VisibilityCue', () => {
	it('carries the meaning as text next to a decorative icon', () => {
		const html = renderToStaticMarkup(<VisibilityCue publicity={0} />);

		expect(html).toContain('data-visibility="private"');
		expect(html).toMatch(/aria-hidden="true".*Private<\/span>$/);
	});
});
