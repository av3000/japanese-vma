// TODO: Revisit chip style https://smart-interface-design-patterns.com/articles/badges-chips-tags-pills/ and make clear usage examples, restyle if needed.
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, within } from 'storybook/test';
import { buttonVariants } from '@/components/shared/Button/types';
import { Chip } from './';

const meta = {
	title: 'Shared/Chip',
	component: Chip,
	args: {
		children: 'Category',
		title: 'Remove Category filter',
		name: 'category',
		value: 'electronics',
		onCancel: fn(),
		onClick: fn(),
	},
	argTypes: {
		children: {
			control: 'text',
			description: 'The content of the chip',
		},
		disabled: {
			control: 'boolean',
			description: 'Whether the chip is disabled',
		},
		title: {
			control: 'text',
			description: 'The title attribute for the chip button',
		},
		name: {
			control: 'text',
			description: 'The name attribute for the chip button',
		},
		value: {
			control: 'text',
			description: 'The value attribute for the chip button',
		},
		onCancel: {
			description: 'Function called when the chip is cancelled (not needed for readonly chips)',
		},
		readonly: {
			control: 'boolean',
			description: 'If true, the chip will not have a remove icon',
		},
		variant: {
			options: buttonVariants,
			control: { type: 'select' },
			description: 'The visual style of the chip (default: secondary-outline)',
		},
		onClick: {
			description: 'Function called when a readonly chip is clicked',
		},
	},
} satisfies Meta<typeof Chip>;

// Important: Export the meta object as the default export
export default meta;

type Story = StoryObj<typeof meta>;

const row = { display: 'flex', gap: '8px', flexWrap: 'wrap' } as const;

export const Default: Story = {
	parameters: {
		docs: {
			description: {
				story: `
## Basic Chip (Removable)

The standard Chip component is used to display selected filters or tags that can be removed by the user.

\`\`\`tsx
<Chip
  title="Remove Category filter"
  name="category"
  value="electronics"
  onCancel={() => handleRemoveFilter('category')}
>
  Category
</Chip>
\`\`\`
        `,
			},
		},
	},
};

/** A removable chip calls `onCancel` when pressed, and a read-only chip never does. */
export const Removable: Story = {
	render: (args) => (
		<div style={row}>
			<Chip {...args}>Removable</Chip>
			<Chip {...args} readonly title="Read-only tag">
				Read-only
			</Chip>
			<Chip {...args} disabled title="Disabled filter">
				Disabled
			</Chip>
		</div>
	),
	play: async ({ args, canvasElement }) => {
		const canvas = within(canvasElement);
		const removable = canvas.getByRole('button', { name: 'Removable' });

		// The title is the description, so a screen reader hears what pressing it does.
		await expect(removable).toHaveAccessibleDescription('Remove Category filter');
		await userEvent.click(removable);
		await expect(args.onCancel).toHaveBeenCalledTimes(1);
		await expect(args.onClick).not.toHaveBeenCalled();

		await userEvent.click(canvas.getByRole('button', { name: 'Read-only' }));
		await expect(args.onCancel).toHaveBeenCalledTimes(1);
		await expect(args.onClick).toHaveBeenCalledTimes(1);

		await expect(canvas.getByRole('button', { name: 'Disabled' })).toBeDisabled();
	},
};

export const ReadonlyChip: Story = {
	args: {
		children: 'JavaScript',
		title: 'JavaScript tag',
		readonly: true,
	},
	parameters: {
		docs: {
			description: {
				story: `
## Readonly Chip

The readonly variant is used for displaying tags or labels that cannot be removed.
These chips don't have the "x" icon and don't require an onCancel callback.

\`\`\`tsx
<Chip
  title="JavaScript tag"
  readonly={true}
>
  JavaScript
</Chip>
\`\`\`
        `,
			},
		},
	},
};

export const ClickableReadonlyChip: Story = {
	args: {
		children: 'React',
		title: 'React tag',
		readonly: true,
	},
	parameters: {
		docs: {
			description: {
				story: `
## Clickable Readonly Chip

Readonly chips can still be clickable by providing an onClick handler.
This is useful for tags that navigate to filtered views.

\`\`\`tsx
<Chip
  title="React tag"
  readonly={true}
  onClick={() => navigateToReactArticles()}
>
  React
</Chip>
\`\`\`
        `,
			},
		},
	},
	play: async ({ args, canvasElement }) => {
		await userEvent.click(within(canvasElement).getByRole('button', { name: 'React' }));
		await expect(args.onClick).toHaveBeenCalledTimes(1);
		await expect(args.onCancel).not.toHaveBeenCalled();
	},
};

export const ChipVariants: Story = {
	render: (args) => (
		<div style={row}>
			{buttonVariants.map((variant) => (
				<Chip key={variant} {...args} variant={variant} title={`Remove ${variant}`}>
					{variant}
				</Chip>
			))}
			<Chip {...args} variant="secondary-outline" readonly title="Read-only">
				Readonly
			</Chip>
		</div>
	),
	parameters: {
		docs: {
			description: {
				story: `
## Chip Variants

Chips support different visual styles through the variant prop.
        `,
			},
		},
	},
};

export const ArticleTags: Story = {
	render: (args) => (
		<div
			style={{
				display: 'flex',
				flexDirection: 'column',
				gap: '16px',
				maxWidth: '600px',
			}}
		>
			<h3>Article Tags Example</h3>
			<p>
				Article tags are typically displayed in a read-only format that users can click to view related
				articles.
			</p>

			<div
				className="article-preview"
				style={{
					padding: '16px',
					border: '1px solid var(--color-neutral-300)',
					borderRadius: '4px',
				}}
			>
				<h4>Getting Started with TypeScript</h4>
				<p>Learn the basics of TypeScript and how to integrate it into your projects...</p>

				<div
					style={{
						display: 'flex',
						gap: '8px',
						marginTop: '16px',
					}}
				>
					{['TypeScript', 'JavaScript', 'Web Development'].map((tag) => (
						<Chip key={tag} {...args} readonly variant="secondary-outline" title={`${tag} tag`}>
							{tag}
						</Chip>
					))}
				</div>
			</div>
		</div>
	),
	parameters: {
		docs: {
			description: {
				story: `
## Article Tags

Readonly chips are ideal for displaying article tags or categories.

\`\`\`tsx
<div style={{ display: 'flex', gap: '8px' }}>
  <Chip readonly onClick={() => filterByTag('TypeScript')}>
    TypeScript
  </Chip>
  <Chip readonly onClick={() => filterByTag('JavaScript')}>
    JavaScript
  </Chip>
  <Chip readonly onClick={() => filterByTag('Web Development')}>
    Web Development
  </Chip>
</div>
\`\`\`
        `,
			},
		},
	},
};

const FILTERS = ['Price: $10-$50', 'Color: Red', 'Size: Medium', 'Category: Electronics'] as const;

export const FilterGroup: Story = {
	render: (args) => (
		<div style={row}>
			{FILTERS.map((filter) => (
				<Chip key={filter} {...args} title={`Remove ${filter.split(':')[0]} filter`}>
					{filter}
				</Chip>
			))}
			<Chip {...args} title="Remove Brand filter" disabled>
				Brand: Apple
			</Chip>
		</div>
	),
	parameters: {
		docs: {
			description: {
				story: `
## Filter Group

Chips are commonly used in groups to represent multiple applied filters.
        `,
			},
		},
	},
};

/** A long Japanese label is cut with an ellipsis inside the chip instead of stretching it. */
export const LongLabel: Story = {
	globals: { viewport: { value: 'xs' } },
	args: {
		children: '新宿駅の新しい改札口についてのお知らせとご案内とその他の情報',
		title: 'Remove title filter',
	},
	render: (args) => (
		<div style={{ maxWidth: '12rem' }}>
			<Chip {...args} />
		</div>
	),
};

export const MixedChipGroup: Story = {
	render: (args) => (
		<div
			style={{
				display: 'flex',
				flexDirection: 'column',
				gap: '16px',
			}}
		>
			<h3>Mixed Chip Types</h3>
			<p>Combining removable filter chips with readonly category chips:</p>

			<div
				style={{
					display: 'flex',
					gap: '8px',
					flexWrap: 'wrap',
					padding: '16px',
					backgroundColor: 'var(--color-neutral-100)',
					borderRadius: '4px',
				}}
			>
				<div style={{ display: 'flex', alignItems: 'center', marginRight: '8px' }}>
					<strong>Categories:</strong>
				</div>

				<Chip {...args} readonly variant="outline" title="Clothing category">
					Clothing
				</Chip>

				<Chip {...args} readonly variant="outline" title="Accessories category">
					Accessories
				</Chip>

				<div style={{ display: 'flex', alignItems: 'center', margin: '0 8px' }}>
					<strong>Active Filters:</strong>
				</div>

				<Chip {...args} title="Remove Size filter">
					Size: Medium
				</Chip>

				<Chip {...args} title="Remove Color filter">
					Color: Blue
				</Chip>
			</div>
		</div>
	),
	parameters: {
		docs: {
			description: {
				story: `
## Mixed Chip Group

You can combine both readonly and removable chips to create interfaces with both
fixed categories and removable filters.
        `,
			},
		},
	},
};
