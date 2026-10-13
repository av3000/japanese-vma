import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, userEvent, within } from 'storybook/test';
import { Field, FieldMessage, Label, Textarea, type ControlSize } from './';

/** `ControlSize` is a type, so the list lives here and the compiler checks it against the type. */
const CONTROL_SIZES = ['sm', 'md'] as const satisfies readonly ControlSize[];

const LONG_JAPANESE =
	'新宿駅では、利用者の混雑を緩和するために、新しい改札口が来月から使えるようになります。工事の期間中は、一部の出口が閉鎖されるため、時間に余裕を持ってお出かけください。';

const meta = {
	title: 'Shared/FormControls/Textarea',
	component: Textarea,
	parameters: { layout: 'padded' },
	args: { 'aria-label': 'Japanese text', placeholder: 'Paste the article text', rows: 4, size: 'md' },
	argTypes: {
		size: { control: 'inline-radio', options: CONTROL_SIZES },
		isInvalid: { control: 'boolean' },
		disabled: { control: 'boolean' },
		noResize: { control: 'boolean' },
	},
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '28rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof Textarea>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const textarea = within(canvasElement).getByRole('textbox', { name: 'Japanese text' });

		await expect(textarea).toBeVisible();
		await expect(textarea).not.toHaveAttribute('aria-invalid');
	},
};

export const Focused: Story = {
	play: async ({ canvasElement }) => {
		await userEvent.tab();
		await expect(within(canvasElement).getByRole('textbox', { name: 'Japanese text' })).toHaveFocus();
	},
};

export const LongJapaneseText: Story = {
	args: { defaultValue: LONG_JAPANESE },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('textbox', { name: 'Japanese text' })).toHaveValue(LONG_JAPANESE);
	},
};

export const Invalid: Story = {
	args: { isInvalid: true },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('textbox', { name: 'Japanese text' })).toHaveAttribute(
			'aria-invalid',
			'true',
		);
	},
};

export const Disabled: Story = {
	args: { disabled: true, defaultValue: 'Not editable' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('textbox', { name: 'Japanese text' })).toBeDisabled();
	},
};

/** `noResize` removes the drag handle, for forms that lay out their own height. */
export const NoResize: Story = {
	args: { noResize: true },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('textbox', { name: 'Japanese text' })).toBeVisible();
	},
};

export const Sizes: Story = {
	render: (args) => (
		<div style={{ display: 'grid', gap: '0.75rem' }}>
			{CONTROL_SIZES.map((size) => (
				<Textarea key={size} {...args} size={size} aria-label={`Size ${size}`} placeholder={`Size ${size}`} />
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const size of CONTROL_SIZES) {
			await expect(canvas.getByRole('textbox', { name: `Size ${size}` })).toBeVisible();
		}
	},
};

const ValidationExample = () => {
	const id = React.useId();

	return (
		<Field>
			<Label htmlFor={id}>Sentence</Label>
			<Textarea id={id} rows={3} defaultValue="犬" isInvalid aria-describedby={`${id}-error`} />
			<FieldMessage id={`${id}-error`} tone="error">
				The sentence must be at least 4 characters.
			</FieldMessage>
		</Field>
	);
};

/** The invalid state is announced: `aria-invalid`, and the message is the control's description. */
export const Validation: Story = {
	render: () => <ValidationExample />,
	play: async ({ canvasElement }) => {
		const textarea = within(canvasElement).getByRole('textbox', { name: 'Sentence' });

		await expect(textarea).toHaveAttribute('aria-invalid', 'true');
		await expect(textarea).toHaveAttribute('aria-describedby', expect.stringMatching(/-error$/));
		await expect(textarea).toHaveAccessibleDescription('The sentence must be at least 4 characters.');
	},
};
