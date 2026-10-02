import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, fn, userEvent, waitFor, within } from '@storybook/test';
import { WRITE_FAILURE_MESSAGES, type WriteFailure } from '@/api/writeFailure';
import { Button } from '@/components/shared/Button';
import { FormPage } from '@/components/shared/FormPage';
import { CatalogueForm, TYPE_LOCKED_HINT, type CatalogueFormValues } from './CatalogueForm';

type CatalogueFormProps = React.ComponentProps<typeof CatalogueForm>;

const EMPTY: CatalogueFormValues = { title: '', type: 5, publicity: false, tags: [] };

const SAVED: CatalogueFormValues = {
	title: 'Trains and stations vocabulary',
	type: 7,
	publicity: true,
	tags: ['trains', 'n4'],
};

const LONGEST: CatalogueFormValues = {
	title: '駅'.repeat(120) + 'x'.repeat(135),
	type: 6,
	publicity: false,
	tags: Array.from({ length: 10 }, (_, index) => `${index}${'station'.repeat(7)}`.slice(0, 50)),
};

const EVERY_FIELD_FAILURE: WriteFailure = {
	kind: 'validation',
	message: WRITE_FAILURE_MESSAGES.validation,
	errors: {
		title: ['The list title must be at least 2 characters'],
		type: ["The type can't be changed once the catalogue has items."],
		tags: ['The tags must be a string.'],
		publicity: ['The publicity field must be true or false.'],
	},
};

/** Answers the first submit with `failure`, the way the route does after a rejected save. */
const RejectingServer = ({ failure, onSubmit, ...props }: CatalogueFormProps) => {
	const [current, setCurrent] = React.useState<WriteFailure | null>(null);

	return (
		<CatalogueForm
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
	title: 'Features/Catalogues/CatalogueForm',
	component: CatalogueForm,
	parameters: { layout: 'fullscreen' },
	args: {
		initialValues: EMPTY,
		onSubmit: fn(),
		submitLabel: 'Create catalogue',
		note: <p>Add items from any kanji, word, radical or sentence page with Save to catalogue.</p>,
		cancel: (
			<Button variant="ghost" to="/catalogues">
				Cancel
			</Button>
		),
	},
	decorators: [
		(Story, { args }) => (
			<FormPage
				title={args.requireChanges ? 'Edit catalogue' : 'New catalogue'}
				size="sm"
				backLink={{ to: '/catalogues', label: 'Catalogues' }}
			>
				<Story />
			</FormPage>
		),
	],
} satisfies Meta<typeof CatalogueForm>;

export default meta;

type Story = StoryObj<typeof meta>;

/** An empty title is caught on submit and focused; the type keeps its number value. */
export const Create: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Create catalogue' }));
		await waitFor(() => expect(canvas.getByLabelText('Title')).toHaveFocus());
		await expect(args.onSubmit).not.toHaveBeenCalled();

		await userEvent.type(canvas.getByLabelText('Title'), 'Station kanji');
		await userEvent.click(canvas.getByRole('radio', { name: 'Kanji' }));
		await userEvent.click(canvas.getByRole('button', { name: 'Create catalogue' }));
		await waitFor(() =>
			expect(args.onSubmit).toHaveBeenCalledWith(
				expect.objectContaining({ title: 'Station kanji', type: 6, publicity: false }),
				expect.anything(),
			),
		);
	},
};

/** A catalogue with items: the type tiles are disabled, say why, and the type is never sent. */
export const EditWithItems: Story = {
	args: {
		initialValues: SAVED,
		submitLabel: 'Save changes',
		requireChanges: true,
		isTypeLocked: true,
		note: undefined,
	},
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('group', { name: 'Type' })).toHaveAccessibleDescription(TYPE_LOCKED_HINT);
		for (const radio of within(canvas.getByRole('group', { name: 'Type' })).getAllByRole('radio')) {
			await expect(radio).toBeDisabled();
		}

		await userEvent.click(canvas.getByRole('button', { name: 'Save changes' }));
		await expect(await canvas.findByRole('status')).toHaveTextContent('No changes to save.');

		await userEvent.type(canvas.getByLabelText('Title'), ' N4');
		await userEvent.click(canvas.getByRole('button', { name: 'Save changes' }));
		await waitFor(() => expect(args.onSubmit).toHaveBeenCalledWith(expect.anything(), { dirtyKeys: ['title'] }));
	},
};

/** Every server message sits under its field; focus moves to the first one. */
export const ServerErrorsOnEveryField: Story = {
	args: { initialValues: SAVED, failure: EVERY_FIELD_FAILURE },
	render: (args) => <RejectingServer {...args} />,
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Create catalogue' }));

		await expect(await canvas.findByRole('alert')).toHaveTextContent(WRITE_FAILURE_MESSAGES.validation);
		await waitFor(() => expect(canvas.getByLabelText('Title')).toHaveFocus());
		await expect(canvas.getByLabelText('Title')).toHaveAccessibleDescription(/at least 2 characters/);
		await expect(canvas.getByRole('group', { name: 'Type' })).toHaveAccessibleDescription(/can't be changed/);
		await expect(canvas.getByLabelText('Tags')).toHaveAccessibleDescription(/must be a string/);
		await expect(canvas.getByRole('group', { name: 'Visibility' })).toHaveAccessibleDescription(/true or false/);
	},
};

/** The longest title and tags the form accepts. Nothing scrolls sideways. */
export const LongestText: Story = {
	args: { initialValues: LONGEST },
	play: async ({ canvasElement }) => {
		const root = canvasElement.ownerDocument.documentElement;
		await expect(root.scrollWidth).toBeLessThanOrEqual(root.clientWidth);
	},
};
