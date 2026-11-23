import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, userEvent, within } from 'storybook/test';
import { Field, FieldMessage, Input, Label, type ControlSize } from './';

/** `ControlSize` is a type, so the list lives here and the compiler checks it against the type. */
const CONTROL_SIZES = ['sm', 'md'] as const satisfies readonly ControlSize[];

const meta = {
	title: 'Shared/FormControls/Input',
	component: Input,
	parameters: { layout: 'padded' },
	args: { 'aria-label': 'Title', placeholder: 'Article title', size: 'md' },
	argTypes: {
		size: { control: 'inline-radio', options: CONTROL_SIZES },
		isInvalid: { control: 'boolean' },
		disabled: { control: 'boolean' },
	},
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '24rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof Input>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const input = within(canvasElement).getByRole('textbox', { name: 'Title' });

		await expect(input).toBeVisible();
		await expect(input).not.toHaveAttribute('aria-invalid');
	},
};

export const Focused: Story = {
	play: async ({ canvasElement }) => {
		await userEvent.tab();
		await expect(within(canvasElement).getByRole('textbox', { name: 'Title' })).toHaveFocus();
	},
};

export const Filled: Story = {
	args: { defaultValue: '新宿駅の新しい改札' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('textbox', { name: 'Title' })).toHaveValue('新宿駅の新しい改札');
	},
};

export const Invalid: Story = {
	args: { isInvalid: true, defaultValue: 'x' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('textbox', { name: 'Title' })).toHaveAttribute(
			'aria-invalid',
			'true',
		);
	},
};

export const Disabled: Story = {
	args: { disabled: true, defaultValue: 'Not editable' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('textbox', { name: 'Title' })).toBeDisabled();
	},
};

export const Sizes: Story = {
	render: (args) => (
		<div style={{ display: 'grid', gap: '0.75rem' }}>
			{CONTROL_SIZES.map((size) => (
				<Input key={size} {...args} size={size} aria-label={`Size ${size}`} placeholder={`Size ${size}`} />
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

export const Types: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '0.75rem' }}>
			<Input type="email" aria-label="Email" placeholder="you@example.com" />
			<Input type="url" aria-label="Source link" placeholder="https://" />
			<Input type="search" aria-label="Search" placeholder="Search kanji" />
			<Input type="password" aria-label="Password" placeholder="Password" />
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('textbox', { name: 'Email' })).toHaveAttribute('type', 'email');
		await expect(canvas.getByLabelText('Password')).toHaveAttribute('type', 'password');
	},
};

const ValidationExample = () => {
	const id = React.useId();

	return (
		<Field>
			<Label htmlFor={id}>Source link</Label>
			<Input id={id} type="url" defaultValue="not a link" isInvalid aria-describedby={`${id}-error`} />
			<FieldMessage id={`${id}-error`} tone="error">
				Enter a full link, starting with https://.
			</FieldMessage>
		</Field>
	);
};

/** The invalid state is announced: `aria-invalid`, and the message is the control's description. */
export const Validation: Story = {
	render: () => <ValidationExample />,
	play: async ({ canvasElement }) => {
		const input = within(canvasElement).getByRole('textbox', { name: 'Source link' });

		await expect(input).toHaveAttribute('aria-invalid', 'true');
		await expect(input).toHaveAttribute('aria-describedby', expect.stringMatching(/-error$/));
		await expect(input).toHaveAccessibleDescription('Enter a full link, starting with https://.');
	},
};
