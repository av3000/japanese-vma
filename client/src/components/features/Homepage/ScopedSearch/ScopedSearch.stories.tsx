import type { Meta, StoryObj } from '@storybook/react';
import { expect, userEvent, within } from '@storybook/test';
import { ScopedSearch } from './';

const meta = {
	title: 'Features/Homepage/ScopedSearch',
	component: ScopedSearch,
	tags: ['autodocs'],
	argTypes: {
		defaultScope: { control: 'inline-radio', options: ['articles', 'kanji', 'words', 'sentences', 'radicals'] },
	},
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '56rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof ScopedSearch>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const Kanji: Story = { args: { defaultScope: 'kanji' } };

export const Words: Story = { args: { defaultScope: 'words' } };

export const Sentences: Story = { args: { defaultScope: 'sentences' } };

export const Radicals: Story = { args: { defaultScope: 'radicals' } };

/** Below 768px the scopes take their own horizontally scrollable row. */
export const MobileViewport: Story = {
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};

/**
 * Enter in the input submits to the selected scope. Arrow keys between scopes are the browser's
 * own radio-group behaviour; user-event 14.5 does not simulate it faithfully (its radio walk
 * skips past the next radio), so it is not asserted here.
 */
export const KeyboardSubmit: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const startUrl = window.location.href;

		await userEvent.click(canvas.getByRole('radio', { name: 'Kanji' }));
		await expect(canvas.getByRole('radio', { name: 'Kanji' })).toBeChecked();

		await userEvent.type(canvas.getByRole('searchbox', { name: 'Search kanji' }), '  水 {Enter}');
		await expect(`${window.location.pathname}${window.location.search}`).toBe('/kanjis?keyword=%E6%B0%B4');

		// The preview's BrowserRouter pushed a real history entry; put the story URL back.
		window.history.replaceState(window.history.state, '', startUrl);
	},
};
