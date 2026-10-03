import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, userEvent, within } from '@storybook/test';
import { Button } from '@/components/shared/Button';
import { FormField } from '@/components/shared/FormControls';
import { InputTags } from './';

const meta = {
	title: 'Components/InputTags',
	component: InputTags,
	tags: ['autodocs'],
} satisfies Meta<typeof InputTags>;

export default meta;

type Story = StoryObj<typeof meta>;

const ControlledExample = () => {
	const [tags, setTags] = React.useState<string[]>(['Kana', 'Grammar']);

	return (
		<div style={{ maxWidth: '520px' }}>
			<InputTags
				label="Controlled"
				value={tags}
				onChange={setTags}
				placeholder="Type a tag and press Enter, comma, or space"
			/>
			<div style={{ display: 'flex', gap: 8, marginTop: 8, flexWrap: 'wrap' }}>
				<Button size="sm" variant="ghost" onClick={() => setTags([])}>
					Clear
				</Button>
				<Button size="sm" variant="secondary" onClick={() => setTags(['N5', 'Kanji'])}>
					Set Example Tags
				</Button>
			</div>
			<div style={{ marginTop: '8px', fontSize: '12px' }}>
				Parent state: {tags.length ? tags.join(', ') : 'None'}
			</div>
		</div>
	);
};

const UncontrolledExample = () => {
	const [lastOnChangeValue, setLastOnChangeValue] = React.useState<string[]>([]);

	return (
		<div style={{ maxWidth: 520 }}>
			<InputTags
				label="Uncontrolled"
				defaultTags={['Kana', 'Grammar']}
				onChange={setLastOnChangeValue}
				placeholder="Type a tag and press Enter, comma, or space"
			/>
			<div style={{ marginTop: '8px', fontSize: '12px' }}>
				Last `onChange` value: {lastOnChangeValue.length ? lastOnChangeValue.join(', ') : 'None'}
			</div>
			<div style={{ marginTop: '4px', fontSize: '12px', color: '#6c757d' }}>
				This instance manages its own tags internally (no `value` prop). `onChange` is optional.
			</div>
		</div>
	);
};

export const Controlled: Story = {
	render: () => {
		return <ControlledExample />;
	},
	parameters: {
		docs: {
			description: {
				story: `Uses \`value\` + \`onChange\`: the parent owns the tags state.`,
			},
		},
	},
};

export const Uncontrolled: Story = {
	render: () => {
		return <UncontrolledExample />;
	},
	parameters: {
		docs: {
			description: {
				story: `Uses \`defaultValue\`: the component owns the tags state internally (still emits \`onChange\`).`,
			},
		},
	},
};

export const ControlledVsUncontrolled: Story = {
	render: () => {
		return (
			<div style={{ display: 'grid', gridTemplateColumns: '1fr', gap: 16, maxWidth: 720 }}>
				<ControlledExample />
				<UncontrolledExample />
			</div>
		);
	},
	parameters: {
		docs: {
			description: {
				story: `A side-by-side comparison of controlled vs uncontrolled usage.`,
			},
		},
	},
};

const InFormFieldExample = ({ error }: { error?: string }) => {
	const [tags, setTags] = React.useState<string[]>(['shinjuku', 'station']);
	const ref = React.useRef<HTMLInputElement>(null);

	return (
		<div style={{ maxWidth: 520 }}>
			<FormField label="Tags" hint="Up to 10 tags, 50 characters each." error={error}>
				{(control) => (
					<InputTags
						ref={ref}
						value={tags}
						onChange={setTags}
						maxTags={10}
						maxTagLength={50}
						aria-describedby={control['aria-describedby']}
						isInvalid={control.isInvalid}
						id={control.id}
					/>
				)}
			</FormField>
			<Button size="sm" variant="ghost" onClick={() => ref.current?.focus()}>
				Focus tags
			</Button>
		</div>
	);
};

/** Inside `FormField`: the label, hint and error reach the text input, and the ref focuses it. */
export const InFormField: Story = {
	render: () => <InFormFieldExample />,
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const input = canvas.getByLabelText('Tags');
		await expect(input).toHaveAccessibleDescription('Up to 10 tags, 50 characters each.');
		await userEvent.click(canvas.getByRole('button', { name: 'Focus tags' }));
		await expect(input).toHaveFocus();
	},
};

export const Invalid: Story = {
	render: () => <InFormFieldExample error="Each tag must be at most 50 characters." />,
	play: async ({ canvasElement }) => {
		const input = within(canvasElement).getByLabelText('Tags');
		await expect(input).toHaveAttribute('aria-invalid', 'true');
		await expect(input).toHaveAccessibleDescription(/at most 50 characters/);
	},
};
