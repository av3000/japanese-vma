import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { Field, FieldMessage, Input, Label } from './';

const meta = {
	title: 'Shared/FormControls/FieldMessage',
	component: FieldMessage,
	parameters: { layout: 'padded' },
	args: { tone: 'hint', children: 'Letters, numbers, underscores and hyphens.' },
	argTypes: {
		tone: { control: 'inline-radio', options: ['hint', 'error'] },
		alignEnd: { control: 'boolean' },
	},
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '24rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof FieldMessage>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Hint: Story = {
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Letters, numbers, underscores and hyphens.')).toBeVisible();
	},
};

export const ErrorMessage: Story = {
	args: { tone: 'error', children: 'The email has already been taken.' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('The email has already been taken.')).toBeVisible();
	},
};

/** `alignEnd` pushes the message to the right, for a character counter. */
export const AlignedEnd: Story = {
	args: { alignEnd: true, children: '12 / 255' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('12 / 255')).toBeVisible();
	},
};

/** Empty children render nothing, so a form can pass an optional message without a stray paragraph. */
export const Empty: Story = {
	args: { children: '' },
	play: async ({ canvasElement }) => {
		await expect(canvasElement.querySelector('p')).toBeNull();
	},
};

export const LongJapanese: Story = {
	args: {
		tone: 'error',
		children: '日本語の文章は四文字以上三百文字以下で入力してください。句読点も文字数に含まれます。',
	},
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText(/日本語の文章は/)).toBeVisible();
	},
};

const DescribedExample = () => {
	const id = React.useId();

	return (
		<Field>
			<Label htmlFor={id}>Username</Label>
			<Input id={id} isInvalid aria-describedby={`${id}-error`} />
			<FieldMessage id={`${id}-error`} tone="error">
				That username is taken.
			</FieldMessage>
		</Field>
	);
};

/** Given an `id`, the message becomes the control's description through `aria-describedby`. */
export const DescribesAControl: Story = {
	render: () => <DescribedExample />,
	play: async ({ canvasElement }) => {
		const input = within(canvasElement).getByRole('textbox', { name: 'Username' });

		await expect(input).toHaveAttribute('aria-describedby', expect.stringMatching(/-error$/));
		await expect(input).toHaveAccessibleDescription('That username is taken.');
	},
};
