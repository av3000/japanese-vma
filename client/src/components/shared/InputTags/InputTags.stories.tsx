import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { Button } from '@/components/shared/Button';
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
		<div style={{ maxWidth: 520 }}>
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
				defaultValue={['Kana', 'Grammar']}
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
