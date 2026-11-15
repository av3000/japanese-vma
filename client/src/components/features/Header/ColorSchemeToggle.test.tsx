/**
 * @vitest-environment jsdom
 */
import { afterEach, describe, expect, it, vi } from 'vitest';
import { icons } from '@/assets/icons';
import { renderWithAct } from '@/test/renderWithAct';
import { AppearanceContext, type AppearanceContextValue, DEFAULT_APPEARANCE } from '@/themes/AppearanceProvider';
import { themeRegistry } from '@/themes/registry';
import { ColorSchemeToggle } from './ColorSchemeToggle';

let view: Awaited<ReturnType<typeof renderWithAct>> | undefined;

const renderToggle = async (value: Partial<AppearanceContextValue>) => {
	view = await renderWithAct(
		<AppearanceContext.Provider value={{ ...DEFAULT_APPEARANCE, ...value }}>
			<ColorSchemeToggle />
		</AppearanceContext.Provider>,
	);
	return view.container.querySelector('button');
};

/** The start of an icon's SVG body, to tell which drawing is on screen. */
const drawing = (name: keyof typeof icons) => icons[name].replace(/<svg[^>]*>/, '').slice(0, 80);

describe('ColorSchemeToggle', () => {
	afterEach(async () => {
		await view?.unmount();
		view = undefined;
	});

	it('is a toggle button named "Dark mode", not pressed while light is on screen', async () => {
		const button = await renderToggle({ scheme: 'light' });

		expect(button?.getAttribute('aria-label')).toBe('Dark mode');
		expect(button?.getAttribute('aria-pressed')).toBe('false');
		expect(button?.getAttribute('title')).toBe('Switch to dark mode');
		expect(button?.innerHTML).toContain(drawing(themeRegistry.kinari.icons.schemeLight));
	});

	it('is pressed, with the same name, while dark is on screen', async () => {
		const button = await renderToggle({ scheme: 'dark' });

		expect(button?.getAttribute('aria-label')).toBe('Dark mode');
		expect(button?.getAttribute('aria-pressed')).toBe('true');
		expect(button?.getAttribute('title')).toBe('Switch to light mode');
		expect(button?.innerHTML).toContain(drawing(themeRegistry.kinari.icons.schemeDark));
	});

	it('draws the scheme icons from the theme’s slots', async () => {
		const button = await renderToggle({ theme: 'sakura', scheme: 'light' });

		expect(button?.innerHTML).toContain(drawing(themeRegistry.sakura.icons.schemeLight));
	});

	it('switches the scheme on click', async () => {
		const toggleScheme = vi.fn();
		const button = await renderToggle({ toggleScheme });

		await view?.flush(() => button?.click());

		expect(toggleScheme).toHaveBeenCalledTimes(1);
	});

	it('is not rendered for a single-scheme theme', async () => {
		const button = await renderToggle({ theme: 'sumi', scheme: 'dark', canToggleScheme: false });

		expect(button).toBeNull();
	});
});
