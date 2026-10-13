import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, userEvent, within } from 'storybook/test';
import { Field, FieldMessage, Label, Select, type ControlSize } from './';

/** `ControlSize` is a type, so the list lives here and the compiler checks it against the type. */
const CONTROL_SIZES = ['sm', 'md'] as const satisfies readonly ControlSize[];

const LEVELS = ['N5', 'N4', 'N3', 'N2', 'N1'] as const;

const LevelOptions = () => (
	<>
		<option value="">Any level</option>
		{LEVELS.map((level) => (
			<option key={level} value={level}>
				{level}
			</option>
		))}
	</>
);

const meta = {
	title: 'Shared/FormControls/Select',
	component: Select,
	parameters: { layout: 'padded' },
	args: { 'aria-label': 'JLPT level', size: 'md', children: <LevelOptions /> },
	argTypes: {
		size: { control: 'inline-radio', options: CONTROL_SIZES },
		isInvalid: { control: 'boolean' },
		disabled: { control: 'boolean' },
	},
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '20rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof Select>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const select = within(canvasElement).getByRole('combobox', { name: 'JLPT level' });

		await expect(select).toBeVisible();
		await expect(select).not.toHaveAttribute('aria-invalid');
	},
};

export const Focused: Story = {
	play: async ({ canvasElement }) => {
		await userEvent.tab();
		await expect(within(canvasElement).getByRole('combobox', { name: 'JLPT level' })).toHaveFocus();
	},
};

export const Selecting: Story = {
	play: async ({ canvasElement }) => {
		const select = within(canvasElement).getByRole('combobox', { name: 'JLPT level' });

		await userEvent.selectOptions(select, 'N3');
		await expect(select).toHaveValue('N3');
	},
};

export const Invalid: Story = {
	args: { isInvalid: true },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('combobox', { name: 'JLPT level' })).toHaveAttribute(
			'aria-invalid',
			'true',
		);
	},
};

export const Disabled: Story = {
	args: { disabled: true, defaultValue: 'N5' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('combobox', { name: 'JLPT level' })).toBeDisabled();
	},
};

export const Sizes: Story = {
	render: (args) => (
		<div style={{ display: 'grid', gap: '0.75rem' }}>
			{CONTROL_SIZES.map((size) => (
				<Select key={size} {...args} size={size} aria-label={`Size ${size}`}>
					<LevelOptions />
				</Select>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const size of CONTROL_SIZES) {
			await expect(canvas.getByRole('combobox', { name: `Size ${size}` })).toBeVisible();
		}
	},
};

const ValidationExample = () => {
	const id = React.useId();

	return (
		<Field>
			<Label htmlFor={id}>Level</Label>
			<Select id={id} isInvalid aria-describedby={`${id}-error`}>
				<LevelOptions />
			</Select>
			<FieldMessage id={`${id}-error`} tone="error">
				Choose a level.
			</FieldMessage>
		</Field>
	);
};

/** The invalid state is announced: `aria-invalid`, and the message is the control's description. */
export const Validation: Story = {
	render: () => <ValidationExample />,
	play: async ({ canvasElement }) => {
		const select = within(canvasElement).getByRole('combobox', { name: 'Level' });

		await expect(select).toHaveAttribute('aria-invalid', 'true');
		await expect(select).toHaveAttribute('aria-describedby', expect.stringMatching(/-error$/));
		await expect(select).toHaveAccessibleDescription('Choose a level.');
	},
};
