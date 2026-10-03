import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, fn, userEvent, waitFor, within } from '@storybook/test';
import { API_ERROR_MESSAGES, type ApiError } from '@/api/apiError';
import { Button } from '@/components/shared/Button';
import { FormPage } from '@/components/shared/FormPage';
import { SentenceForm } from './index';

type SentenceFormProps = React.ComponentProps<typeof SentenceForm>;

/** Answers the first submit with `apiError`, the way the route does after a rejected save. */
const RejectingServer = ({ apiError, onSubmit, ...props }: SentenceFormProps) => {
	const [current, setCurrent] = React.useState<ApiError | null>(null);

	return (
		<SentenceForm
			{...props}
			apiError={current}
			onSubmit={(values) => {
				onSubmit(values);
				setCurrent(apiError ?? null);
			}}
		/>
	);
};

const meta = {
	title: 'Features/Japanese/SentenceForm',
	component: SentenceForm,
	parameters: { layout: 'fullscreen' },
	args: {
		initialValues: { content: '' },
		onSubmit: fn(),
		submitLabel: 'Create sentence',
		cancel: (
			<Button variant="ghost" to="/sentences">
				Cancel
			</Button>
		),
	},
	decorators: [
		(Story, { args }) => (
			<FormPage
				title={args.requireChanges ? 'Edit sentence' : 'New sentence'}
				size="sm"
				backLink={{ to: '/sentences', label: 'Sentences' }}
			>
				<Story />
			</FormPage>
		),
	],
} satisfies Meta<typeof SentenceForm>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Create: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Create sentence' }));

		await waitFor(() => expect(canvas.getByLabelText('Sentence')).toHaveFocus());
		await expect(canvas.getByLabelText('Sentence')).toHaveAccessibleDescription(
			'Between 4 and 300 characters. Sentence is required.',
		);
		await expect(args.onSubmit).not.toHaveBeenCalled();
	},
};

export const ServerError: Story = {
	args: {
		initialValues: { content: '水を飲みます。' },
		apiError: {
			kind: 'validation',
			message: API_ERROR_MESSAGES.validation,
			errors: { content: ['The content has already been taken.'] },
		},
	},
	render: (args) => <RejectingServer {...args} />,
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Create sentence' }));

		await expect(await canvas.findByRole('alert')).toHaveTextContent(API_ERROR_MESSAGES.validation);
		await waitFor(() => expect(canvas.getByLabelText('Sentence')).toHaveFocus());
		await expect(canvas.getByLabelText('Sentence')).toHaveAccessibleDescription(/already been taken/);
	},
};

export const EditUnchanged: Story = {
	args: { initialValues: { content: '水を飲みます。' }, submitLabel: 'Save changes', requireChanges: true },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Save changes' }));
		await expect(await canvas.findByRole('status')).toHaveTextContent('No changes to save.');
		await expect(args.onSubmit).not.toHaveBeenCalled();
	},
};

/** A 300-character sentence without spaces wraps inside the card. */
export const LongestText: Story = {
	args: { initialValues: { content: '新宿駅の新しい改札が完成しました。'.repeat(18).slice(0, 300) } },
	play: async ({ canvasElement }) => {
		const root = canvasElement.ownerDocument.documentElement;
		await expect(root.scrollWidth).toBeLessThanOrEqual(root.clientWidth);
	},
};
