import { renderToStaticMarkup } from 'react-dom/server';
import { LikeIcon, type LikeIconProps } from '.';
import { describe, expect, it } from 'vitest';
import { icons } from '@/assets/icons';
import { AppearanceContext, DEFAULT_APPEARANCE } from '@/themes/AppearanceProvider';
import { THEME_IDS, type ThemeId } from '@/themes/manifest';
import { themeRegistry } from '@/themes/registry';

const render = (theme: ThemeId, props: LikeIconProps) =>
	renderToStaticMarkup(
		<AppearanceContext.Provider value={{ ...DEFAULT_APPEARANCE, theme }}>
			<LikeIcon {...props} />
		</AppearanceContext.Provider>,
	);

/** The icon's SVG body, which is how the drawing on screen can be told apart. */
const drawing = (name: keyof typeof icons) => icons[name].replace(/<svg[^>]*>/, '').slice(0, 120);

describe.each(THEME_IDS)('LikeIcon in %s', (theme) => {
	const slots = themeRegistry[theme].icons;

	it('draws the likeOff slot when not liked', () => {
		const html = render(theme, { liked: false });
		expect(html).toContain('data-state="off"');
		expect(html).toContain(drawing(slots.likeOff));
	});

	it('draws the likeOn slot when liked', () => {
		const html = render(theme, { liked: true });
		expect(html).toContain('data-state="on"');
		expect(html).toContain(drawing(slots.likeOn));
	});

	it('draws the likeOn shape next to a count, whatever the liked state', () => {
		const html = render(theme, { tone: 'count', liked: false });
		expect(html).toContain('data-state="count"');
		expect(html).toContain(drawing(slots.likeOn));
	});
});
