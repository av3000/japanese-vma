import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { FormField, Input, Textarea } from './';

const meta = {
	title: 'Shared/FormControls/FormField',
	component: FormField,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: {
		label: 'Email',
		children: (control) => <Input type="email" autoComplete="email" {...control} />,
	},
} satisfies Meta<typeof FormField>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const input = within(canvasElement).getByLabelText('Email');
		await expect(input).not.toHaveAttribute('aria-describedby');
		await expect(input).not.toHaveAttribute('aria-invalid');
	},
};

export const WithHint: Story = {
	args: {
		label: 'Username',
		hint: 'Letters, numbers, underscores and hyphens. Other learners see this name.',
		children: (control) => <Input autoComplete="nickname" {...control} />,
	},
	play: async ({ canvasElement }) => {
		const input = within(canvasElement).getByLabelText('Username');
		await expect(input).toHaveAccessibleDescription(/Letters, numbers/);
	},
};

export const WithError: Story = {
	args: { error: 'The email has already been taken.' },
	play: async ({ canvasElement }) => {
		const input = within(canvasElement).getByLabelText('Email');
		await expect(input).toHaveAttribute('aria-invalid', 'true');
		await expect(input).toHaveAccessibleDescription('The email has already been taken.');
	},
};

/** Every rule the server rejects shows on its own line, under a hint that stays visible. */
export const WithHintAndErrors: Story = {
	args: {
		label: 'Password',
		hint: 'At least 8 characters, with upper- and lowercase letters, a number and a symbol.',
		error: [
			'The password must be at least 8 characters.',
			'The password field must contain at least one uppercase and one lowercase letter.',
			'The password field must contain at least one number.',
			'The password field must contain at least one symbol.',
		],
		children: (control) => <Input type="password" autoComplete="new-password" {...control} />,
	},
};

export const WrappingATextarea: Story = {
	args: {
		label: 'Description',
		hint: 'Shown on the catalogue page.',
		children: (control) => <Textarea rows={4} {...control} />,
	},
};

/** Long labels and messages wrap inside a narrow column. */
export const LongLabelNarrow: Story = {
	args: {
		label: 'The email address you want to use to log in and receive account notices',
		error: 'The email field must be a valid email address with a domain that accepts mail.',
	},
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};

/** The counter sits on the hint row and stays out of the accessible description. */
export const WithCounter: Story = {
	args: {
		label: 'Japanese title',
		hint: 'Up to 255 characters.',
		counter: '14 / 255',
		children: (control) => <Input defaultValue="新宿駅の新しい改札が完成" {...control} />,
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByLabelText('Japanese title')).toHaveAccessibleDescription('Up to 255 characters.');
		await expect(canvas.getByText('14 / 255')).toBeVisible();
	},
};
