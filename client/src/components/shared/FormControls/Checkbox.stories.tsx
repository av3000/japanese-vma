import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, within } from 'storybook/test';
import { Checkbox, FieldMessage } from './';

const meta = {
	title: 'Shared/FormControls/Checkbox',
	component: Checkbox,
	parameters: { layout: 'padded' },
	args: { label: 'Make this article public', onChange: fn() },
	argTypes: {
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
} satisfies Meta<typeof Checkbox>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const checkbox = within(canvasElement).getByRole('checkbox', { name: 'Make this article public' });

		await expect(checkbox).toBeVisible();
		await expect(checkbox).not.toBeChecked();
		await expect(checkbox).not.toHaveAttribute('aria-invalid');
	},
};

export const Toggling: Story = {
	play: async ({ args, canvasElement }) => {
		const checkbox = within(canvasElement).getByRole('checkbox', { name: 'Make this article public' });

		await userEvent.click(checkbox);
		await expect(checkbox).toBeChecked();
		await expect(args.onChange).toHaveBeenCalledTimes(1);

		await userEvent.keyboard(' ');
		await expect(checkbox).not.toBeChecked();
	},
};

export const Focused: Story = {
	play: async ({ canvasElement }) => {
		await userEvent.tab();
		await expect(within(canvasElement).getByRole('checkbox', { name: 'Make this article public' })).toHaveFocus();
	},
};

export const Checked: Story = {
	args: { defaultChecked: true },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('checkbox', { name: 'Make this article public' })).toBeChecked();
	},
};

export const Invalid: Story = {
	args: { isInvalid: true },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('checkbox', { name: 'Make this article public' })).toHaveAttribute(
			'aria-invalid',
			'true',
		);
	},
};

export const Disabled: Story = {
	args: { disabled: true, defaultChecked: true },
	play: async ({ args, canvasElement }) => {
		const checkbox = within(canvasElement).getByRole('checkbox', { name: 'Make this article public' });

		await expect(checkbox).toBeDisabled();
		await userEvent.click(checkbox);
		await expect(args.onChange).not.toHaveBeenCalled();
	},
};

/** A long label wraps beside the box instead of pushing it off the line. */
export const LongLabel: Story = {
	args: {
		label: 'I understand that the article, its analysed kanji and its words become visible to every learner, and that I can switch it back to private at any time from the article page.',
	},
	globals: { viewport: { value: 'xs' } },
	play: async ({ canvasElement }) => {
		await expect(
			within(canvasElement).getByRole('checkbox', { name: /I understand that the article/ }),
		).toBeVisible();
	},
};

const ValidationExample = () => {
	const id = React.useId();

	return (
		<div>
			<Checkbox label="I accept the community rules" isInvalid aria-describedby={`${id}-error`} />
			<FieldMessage id={`${id}-error`} tone="error">
				You must accept the rules to post.
			</FieldMessage>
		</div>
	);
};

/** The invalid state is announced: `aria-invalid`, and the message is the control's description. */
export const Validation: Story = {
	render: () => <ValidationExample />,
	play: async ({ canvasElement }) => {
		const checkbox = within(canvasElement).getByRole('checkbox', { name: 'I accept the community rules' });

		await expect(checkbox).toHaveAttribute('aria-invalid', 'true');
		await expect(checkbox).toHaveAttribute('aria-describedby', expect.stringMatching(/-error$/));
		await expect(checkbox).toHaveAccessibleDescription('You must accept the rules to post.');
	},
};
