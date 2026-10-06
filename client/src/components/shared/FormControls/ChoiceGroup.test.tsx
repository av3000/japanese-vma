import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { ChoiceGroup, type ChoiceOption } from './';

const VISIBILITY: ChoiceOption<'public' | 'private'>[] = [
	{ value: 'public', label: 'Public', description: 'Anyone can find and read it.' },
	{ value: 'private', label: 'Private', description: 'Only you can see it.' },
];

const render = (props: Partial<React.ComponentProps<typeof ChoiceGroup<'public' | 'private'>>> = {}) =>
	renderToStaticMarkup(
		<ChoiceGroup
			id="visibility"
			legend="Visibility"
			name="publicity"
			options={VISIBILITY}
			value="public"
			onChange={() => undefined}
			{...props}
		/>,
	);

describe('ChoiceGroup', () => {
	it('names the group with a legend and renders one native radio per option', () => {
		const html = render();

		expect(html).toMatch(/<fieldset[^>]*><legend[^>]*>Visibility<\/legend>/);
		expect(html.match(/type="radio"/g)).toHaveLength(2);
		expect(html.match(/name="publicity"/g)).toHaveLength(2);
		expect(html).toMatch(/<input[^>]*id="visibility-0"[^>]*checked=""/);
		expect(html).not.toMatch(/<input[^>]*id="visibility-1"[^>]*checked=""/);
	});

	it('describes each radio by its own description', () => {
		const html = render();

		expect(html).toMatch(/<input[^>]*aria-describedby="visibility-1-description"/);
		expect(html).toMatch(/<span id="visibility-1-description"[^>]*>Only you can see it\.<\/span>/);
	});

	it('lists the hint and then the error on the fieldset', () => {
		const html = render({ hint: 'Public articles are readable straight away.', error: 'Pick one.' });

		expect(html).toMatch(/<fieldset[^>]*aria-describedby="visibility-hint visibility-error"/);
		expect(html).toMatch(/<div id="visibility-error"><p[^>]*>Pick one\.<\/p><\/div>/);
	});

	it('omits aria-describedby without hint or error', () => {
		const html = render();

		expect(html).not.toMatch(/<fieldset[^>]*aria-describedby/);
	});

	it('disables every radio through the fieldset', () => {
		expect(render({ disabled: true })).toMatch(/<fieldset[^>]*disabled=""/);
	});

	it('hides the decorative glyph from assistive technology', () => {
		const html = renderToStaticMarkup(
			<ChoiceGroup
				legend="Type"
				name="type"
				options={[{ value: 6, label: 'Kanji', glyph: '漢' }]}
				value={6}
				onChange={() => undefined}
			/>,
		);

		expect(html).toMatch(/<span[^>]*aria-hidden="true"[^>]*>漢<\/span>/);
		expect(html).toContain('value="6"');
	});
});
