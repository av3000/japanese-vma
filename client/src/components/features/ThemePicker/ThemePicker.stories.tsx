import type { Meta, StoryObj } from '@storybook/react-vite';
import { ThemePicker } from '.';
import { expect, within } from 'storybook/test';
import { THEME_IDS } from '@/themes/manifest';

/** In the footer for every visitor. Picking a theme here also switches Storybook's Theme toolbar. */
const meta = {
	title: 'Features/Navigation/ThemePicker',
	component: ThemePicker,
	parameters: { layout: 'padded' },
} satisfies Meta<typeof ThemePicker>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const select = within(canvasElement).getByRole('combobox', { name: 'Theme' });
		await expect(select.querySelectorAll('option')).toHaveLength(THEME_IDS.length);
	},
};
