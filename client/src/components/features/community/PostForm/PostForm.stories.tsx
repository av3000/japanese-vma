import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, fn, userEvent, waitFor, within } from '@storybook/test';
import { WRITE_FAILURE_MESSAGES, type WriteFailure } from '@/api/writeFailure';
import { Button } from '@/components/shared/Button';
import { FormPage } from '@/components/shared/FormPage';
import { PostForm } from './index';
import type { PostFormValues } from './postFormSchema';

type PostFormProps = React.ComponentProps<typeof PostForm>;

const EMPTY: PostFormValues = { title: '', content: '', topic: 1, tags: [] };

const SAVED: PostFormValues = {
	title: 'How do you review kanji you met in articles?',
	content:
		'I save kanji from every article into a list but I never go back to them. Do you export the PDF, use an SRS app, or re-read the article? Curious what works for N3 learners.',
	topic: 1,
	tags: ['howto', 'kanji'],
};

const LONGEST: PostFormValues = {
	title: 'x'.repeat(255),
	content: 'I save kanji from every article into a list but never go back to them. '.repeat(209).slice(0, 15000),
	topic: 1,
	tags: Array.from({ length: 10 }, (_, index) => `${index}${'suggestion'.repeat(5)}`.slice(0, 50)),
};

const EVERY_FIELD_FAILURE: WriteFailure = {
	kind: 'validation',
	message: WRITE_FAILURE_MESSAGES.validation,
	errors: {
		title: ['The title must be at least 2 characters.'],
		content: ['The content must be at least 5 characters.'],
		type: ['The selected topic is invalid.'],
		'hashtags.1': ['The hashtags.1 field must not be greater than 50 characters.'],
	},
};

/** Answers the first submit with `failure`, the way the route does after a rejected save. */
const RejectingServer = ({ failure, onSubmit, ...props }: PostFormProps) => {
	const [current, setCurrent] = React.useState<WriteFailure | null>(null);

	return (
		<PostForm
			{...props}
			failure={current}
			onSubmit={(values, meta) => {
				onSubmit(values, meta);
				setCurrent(failure ?? null);
			}}
		/>
	);
};

const meta = {
	title: 'Features/Community/PostForm',
	component: PostForm,
	parameters: { layout: 'fullscreen' },
	args: {
		initialValues: EMPTY,
		onSubmit: fn(),
		submitLabel: 'Publish post',
		cancel: (
			<Button variant="ghost" to="/community">
				Cancel
			</Button>
		),
	},
	decorators: [
		(Story, { args }) => (
			<FormPage
				title={args.requireChanges ? 'Edit post' : 'New post'}
				backLink={{ to: '/community', label: 'Community' }}
			>
				<Story />
			</FormPage>
		),
	],
} satisfies Meta<typeof PostForm>;

export default meta;

type Story = StoryObj<typeof meta>;

/** An empty submit focuses the title and sends nothing. */
export const Create: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Publish post' }));

		await waitFor(() => expect(canvas.getByLabelText('Title')).toHaveFocus());
		await expect(canvas.getByLabelText('Title')).toHaveAccessibleDescription('Title is required.');
		await expect(canvas.getByLabelText('Text')).toHaveAccessibleDescription('Content is required.');
		await expect(args.onSubmit).not.toHaveBeenCalled();
	},
};

/** Server names (`type`, `hashtags.1`) land on their form fields; focus moves to the first. */
export const ServerErrorsOnEveryField: Story = {
	args: { initialValues: SAVED, failure: EVERY_FIELD_FAILURE },
	render: (args) => <RejectingServer {...args} />,
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Publish post' }));

		await expect(await canvas.findByRole('alert')).toHaveTextContent(WRITE_FAILURE_MESSAGES.validation);
		await waitFor(() => expect(canvas.getByLabelText('Title')).toHaveFocus());
		await expect(canvas.getByLabelText('Text')).toHaveAccessibleDescription(/at least 5/);
		await expect(canvas.getByLabelText('Topic')).toHaveAccessibleDescription(/topic is invalid/);
		await expect(canvas.getByLabelText('Tags')).toHaveAccessibleDescription(/greater than 50/);
	},
};

/** An unchanged edit says so and sends nothing; a real change sends only that field. */
export const EditUnchanged: Story = {
	args: { initialValues: SAVED, submitLabel: 'Save changes', requireChanges: true },
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Save changes' }));
		await expect(await canvas.findByRole('status')).toHaveTextContent('No changes to save.');
		await expect(args.onSubmit).not.toHaveBeenCalled();

		await userEvent.selectOptions(canvas.getByLabelText('Topic'), '5');
		await userEvent.click(canvas.getByRole('button', { name: 'Save changes' }));
		await waitFor(() => expect(args.onSubmit).toHaveBeenCalledWith(expect.anything(), { dirtyKeys: ['topic'] }));
	},
};

/** The longest post the server accepts. Nothing scrolls sideways. */
export const LongestText: Story = {
	args: { initialValues: LONGEST },
	play: async ({ canvasElement }) => {
		const root = canvasElement.ownerDocument.documentElement;
		await expect(root.scrollWidth).toBeLessThanOrEqual(root.clientWidth);
	},
};
