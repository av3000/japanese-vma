import type { Meta, StoryObj } from '@storybook/react';
import { Icon } from '@/components/shared/Icon';
import { Chip } from './';

const meta = {
	title: 'UI/Chip',
	component: Chip,
	tags: ['autodocs'],
	argTypes: {
		children: { control: 'text' },
		readonly: { control: 'boolean' },
		disabled: { control: 'boolean' },
		variant: {
			options: ['primary', 'secondary', 'success', 'pending', 'danger', 'outline', 'secondary-outline', 'ghost', 'linkButton'],
			control: { type: 'select' },
		},
		onCancel: { action: 'cancelled' },
		onClick: { action: 'clicked' },
	},
	args: {
		children: 'Filter',
		variant: 'secondary-outline',
		readonly: false,
	},
	parameters: {
		docs: {
			description: {
				component: `
**When to use**

- Use **Chip** for filters, tags, and interactive labels (removable or clickable).
- For compact status/count indicators, use **Badge**.
`,
			},
		},
	},
} satisfies Meta<typeof Chip>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {};

export const Variants: Story = {
	render: () => (
		<div style={{ display: 'flex', gap: 12, flexWrap: 'wrap' }}>
			<Chip variant="primary" onCancel={() => undefined}>
				Primary
			</Chip>
			<Chip variant="secondary" onCancel={() => undefined}>
				Secondary
			</Chip>
			<Chip variant="success" onCancel={() => undefined}>
				Success
			</Chip>
			<Chip variant="pending" onCancel={() => undefined}>
				Pending
			</Chip>
			<Chip variant="danger" onCancel={() => undefined}>
				Destructive
			</Chip>
			<Chip variant="secondary-outline" onCancel={() => undefined}>
				Secondary Outline
			</Chip>
			<Chip variant="outline" onCancel={() => undefined}>
				Outline
			</Chip>
			<Chip variant="ghost" onCancel={() => undefined}>
				Ghost
			</Chip>
			<Chip variant="linkButton" onCancel={() => undefined}>
				Link
			</Chip>
		</div>
	),
};

export const Removable: Story = {
	args: {
		children: 'Category: Science',
		title: 'Remove Category filter',
	},
};

export const ReadonlyTag: Story = {
	args: {
		children: 'Japanese',
		readonly: true,
	},
};

export const FilterGroup: Story = {
	render: () => (
		<div style={{ display: 'flex', gap: 8, flexWrap: 'wrap' }}>
			<Chip title="Remove Level N2" onCancel={() => undefined}>
				Level: N2
			</Chip>
			<Chip title="Remove JLPT filter" onCancel={() => undefined}>
				JLPT
			</Chip>
			<Chip title="Remove Tag" onCancel={() => undefined}>
				Tag: Grammar
			</Chip>
			<Chip disabled title="Disabled filter" onCancel={() => undefined}>
				Disabled
			</Chip>
		</div>
	),
};

export const WithIcon: Story = {
	render: () => (
		<Chip readonly variant="secondary-outline" onClick={() => undefined}>
			<Icon name="bookmarkSolid" size="sm" />
			<span>Tagged</span>
		</Chip>
	),
};
