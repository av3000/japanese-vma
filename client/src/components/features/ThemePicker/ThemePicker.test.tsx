/**
 * @vitest-environment jsdom
 */
import { ThemePicker } from '.';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { renderWithAct } from '@/test/renderWithAct';
import { AppearanceContext, type AppearanceContextValue, DEFAULT_APPEARANCE } from '@/themes/AppearanceProvider';
import { THEME_IDS, themeManifest } from '@/themes/manifest';

let view: Awaited<ReturnType<typeof renderWithAct>> | undefined;

const renderPicker = async (value: Partial<AppearanceContextValue>) => {
	view = await renderWithAct(
		<AppearanceContext.Provider value={{ ...DEFAULT_APPEARANCE, ...value }}>
			<ThemePicker />
		</AppearanceContext.Provider>,
	);
	const select = view.container.querySelector('select');
	const label = select && view.container.querySelector(`label[for="${select.id}"]`);
	return { select, label };
};

describe('ThemePicker', () => {
	afterEach(async () => {
		await view?.unmount();
		view = undefined;
	});

	it('is a select labelled "Theme" listing every theme, with the current one selected', async () => {
		const { select, label } = await renderPicker({ theme: 'sakura' });

		expect(label?.textContent).toBe('Theme');
		expect([...(select?.options ?? [])].map((option) => option.textContent)).toEqual(
			THEME_IDS.map((id) => themeManifest[id].label),
		);
		expect(select?.value).toBe('sakura');
	});

	it('sets the chosen theme', async () => {
		const setTheme = vi.fn();
		const { select } = await renderPicker({ setTheme });

		await view?.flush(() => {
			if (!select) return;
			select.value = 'sumi';
			select.dispatchEvent(new Event('change', { bubbles: true }));
		});

		expect(setTheme).toHaveBeenCalledWith('sumi');
	});
});
