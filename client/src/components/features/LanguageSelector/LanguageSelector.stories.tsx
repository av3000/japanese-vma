import type { Meta, StoryObj } from '@storybook/react-vite';
import { LanguageSelector } from '.';
import { expect, userEvent, within } from 'storybook/test';
import { translationSpies } from '@/storybook/decorators';

/**
 * The header's language picker (#665). It exists only while a second language is active; the real
 * `TranslationProvider` is replaced by `parameters.translation` (see Docs/Authoring).
 */
const meta = {
	title: 'Features/Navigation/LanguageSelector',
	component: LanguageSelector,
	parameters: { layout: 'padded' },
} satisfies Meta<typeof LanguageSelector>;

export default meta;

type Story = StoryObj<typeof meta>;

/** English alone: nothing renders, so the header is exactly what it was before translations. */
export const Hidden: Story = {
	parameters: { translation: 'english' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).queryByRole('combobox')).toBeNull();
		await expect(canvasElement.querySelector('[role="status"]')).toBeNull();
	},
};

/** Two languages: a labelled select naming the current one in text, options in their own language. */
export const TwoLocales: Story = {
	parameters: { translation: 'two' },
	play: async ({ canvasElement }) => {
		const select = within(canvasElement).getByRole('combobox', { name: 'Language' });
		await expect(select).toHaveDisplayValue('English');
		await expect(within(select).getByRole('option', { name: 'Dansk' })).toHaveAttribute('lang', 'da');

		await userEvent.selectOptions(select, 'da');

		await expect(translationSpies.setLocale).toHaveBeenCalledWith('da');
		await expect(select).toHaveDisplayValue('Dansk');
	},
};

/** Keyboard only: focus the select and change it with the arrow keys. */
export const KeyboardOperable: Story = {
	parameters: { translation: 'two' },
	play: async ({ canvasElement }) => {
		const select = within(canvasElement).getByRole('combobox', { name: 'Language' });

		await userEvent.tab();
		await expect(select).toHaveFocus();
		await userEvent.keyboard('{ArrowDown}');

		await expect(translationSpies.setLocale).toHaveBeenCalledWith('da');
	},
};

/** Danish could not be loaded: the labels stay in English, and a polite status line says so. */
export const LoadError: Story = {
	parameters: { translation: 'error' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('status')).toHaveTextContent(
			"Couldn't load that language. Showing English.",
		);
		await expect(within(canvasElement).getByRole('combobox', { name: 'Language' })).toHaveDisplayValue('Dansk');
	},
};

/** The notice in the menu drawer sits under the select instead of floating. */
export const LoadErrorInline: Story = {
	args: { notice: 'inline' },
	parameters: { translation: 'error' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('status')).toHaveTextContent("Couldn't load that language.");
	},
};
