import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, fn, userEvent, within } from '@storybook/test';
import { ChoiceGroup, type ChoiceGroupProps, type ChoiceOption } from './';

const VISIBILITY: ChoiceOption<'public' | 'private'>[] = [
	{ value: 'public', label: 'Public', description: 'Anyone can find and read it.' },
	{ value: 'private', label: 'Private', description: 'Only you can see it.' },
];

const CATALOGUE_TYPES: ChoiceOption<number>[] = [
	{ value: 5, label: 'Radicals', glyph: '部' },
	{ value: 6, label: 'Kanji', glyph: '漢' },
	{ value: 7, label: 'Words', glyph: '語' },
	{ value: 8, label: 'Sentences', glyph: '文' },
	{ value: 9, label: 'Articles', glyph: '記' },
];

/** Holds the value so the story behaves like a form field; `onChange` still reaches the actions panel. */
function Controlled<T extends string | number>({ value: initial, onChange, ...props }: ChoiceGroupProps<T>) {
	const [value, setValue] = React.useState(initial);

	return (
		<div style={{ maxWidth: 720 }}>
			<ChoiceGroup
				{...props}
				value={value}
				onChange={(next) => {
					setValue(next);
					onChange(next);
				}}
			/>
		</div>
	);
}

const meta = {
	title: 'Shared/FormControls/ChoiceGroup',
	component: ChoiceGroup,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
} satisfies Meta<typeof ChoiceGroup>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Two options with their consequence spelled out. Arrow keys move the choice. */
export const Visibility: Story = {
	args: { legend: 'Visibility', name: 'publicity', options: VISIBILITY, value: 'public', onChange: fn() },
	render: (args) => <Controlled {...(args as ChoiceGroupProps<'public' | 'private'>)} />,
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		const group = canvas.getByRole('group', { name: 'Visibility' });
		const publicRadio = canvas.getByRole('radio', { name: 'Public' });
		const privateRadio = canvas.getByRole('radio', { name: 'Private' });

		await expect(group).toBeVisible();
		await expect(publicRadio).toBeChecked();
		await expect(privateRadio).toHaveAccessibleDescription('Only you can see it.');

		await userEvent.click(canvas.getByText('Only you can see it.'));
		await expect(privateRadio).toBeChecked();
		await expect(args.onChange).toHaveBeenLastCalledWith('private');

		await userEvent.keyboard('{ArrowUp}');
		await expect(publicRadio).toBeChecked();
		await expect(publicRadio).toHaveFocus();
		await expect(args.onChange).toHaveBeenLastCalledWith('public');
	},
};

/** Five glyph tiles; the value keeps its number type. */
export const CatalogueType: Story = {
	args: {
		legend: 'Type',
		name: 'type',
		options: CATALOGUE_TYPES,
		value: 6,
		onChange: fn(),
		hint: "The type decides what you can add. It can't be changed once the catalogue has items.",
	},
	render: (args) => <Controlled {...(args as ChoiceGroupProps<number>)} />,
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);

		await userEvent.click(canvas.getByRole('radio', { name: 'Words' }));
		await expect(args.onChange).toHaveBeenLastCalledWith(7);
		await expect(canvas.getByRole('group', { name: 'Type' })).toHaveAccessibleDescription(
			/decides what you can add/,
		);
	},
};

export const WithError: Story = {
	args: {
		legend: 'Type',
		name: 'type',
		options: CATALOGUE_TYPES,
		value: 6,
		onChange: fn(),
		error: "The type can't be changed once the catalogue has items.",
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('group', { name: 'Type' })).toHaveAccessibleDescription(/can't be changed/);
		await expect(canvas.getByText("The type can't be changed once the catalogue has items.")).toBeVisible();
	},
};

/** Disabled, with the reason in the hint. */
export const Disabled: Story = {
	args: {
		legend: 'Type',
		name: 'type',
		options: CATALOGUE_TYPES,
		value: 6,
		onChange: fn(),
		disabled: true,
		hint: "This catalogue has items, so its type can't be changed.",
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		for (const radio of canvas.getAllByRole('radio')) {
			await expect(radio).toBeDisabled();
		}
		await expect(canvas.getByRole('radio', { name: 'Kanji' })).toBeChecked();
	},
};

/** The form-facing ref lands on the checked radio, so a failed submit can focus the group. */
export const FocusThroughRef: Story = {
	args: { legend: 'Visibility', name: 'publicity', options: VISIBILITY, value: 'private', onChange: fn() },
	render: (args) => {
		const Focusable = () => {
			const ref = React.useRef<HTMLInputElement>(null);
			return (
				<>
					<ChoiceGroup {...(args as ChoiceGroupProps<'public' | 'private'>)} inputRef={ref} />
					<button type="button" onClick={() => ref.current?.focus()}>
						Focus group
					</button>
				</>
			);
		};
		return <Focusable />;
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await userEvent.click(canvas.getByRole('button', { name: 'Focus group' }));
		await expect(canvas.getByRole('radio', { name: 'Private' })).toHaveFocus();
	},
};
