import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it } from 'vitest';
import { SavedListType } from '@/shared/constants/enums';
import { AuthorizedBookmarkWidget } from './';

const widget = (props: Partial<React.ComponentProps<typeof AuthorizedBookmarkWidget>> = {}) => (
	<AuthorizedBookmarkWidget
		entityId={42}
		instanceObjectType={SavedListType.KANJIS}
		isKnownType={SavedListType.KNOWNKANJIS}
		loadOnMount={false}
		{...props}
	/>
);

describe('AuthorizedBookmarkWidget', () => {
	it('keeps the detail-page rendering when no table props are given', () => {
		const html = renderToStaticMarkup(widget({ initialIsKnown: false }));

		expect(html).toContain('Not learned');
		expect(html).not.toContain('aria-label=');
	});

	it('names the icon button after the item and its saved state', () => {
		expect(renderToStaticMarkup(widget({ itemLabel: '水' }))).toContain('aria-label="Save 水"');
		expect(renderToStaticMarkup(widget({ itemLabel: '水', initialIsBookmarked: true }))).toContain(
			'aria-label="Saved: 水"',
		);
	});

	it('shows only a Known mark in compact mode', () => {
		const known = renderToStaticMarkup(widget({ compact: true, initialIsKnown: true }));
		const unknown = renderToStaticMarkup(widget({ compact: true, initialIsKnown: false }));

		expect(known).toMatch(/>Known<\/span>/);
		expect(known).not.toContain('Learned');
		expect(unknown).not.toContain('Known');
		expect(unknown).not.toContain('learned');
	});

	it('gives every widget its own dialog id', () => {
		const html = renderToStaticMarkup(
			<>
				{widget()}
				{widget({ entityId: 43 })}
			</>,
		);
		const ids = [...html.matchAll(/aria-controls="([^"]+)"/g)].map((match) => match[1]);

		expect(ids).toHaveLength(2);
		expect(new Set(ids).size).toBe(2);
		expect(ids).not.toContain('sentence-bookmark-modal');
	});
});
