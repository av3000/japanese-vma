// TODO: Revisit chip style https://smart-interface-design-patterns.com/articles/badges-chips-tags-pills/ and make clear usage examples, restyle if needed.
import type { Meta, StoryObj } from '@storybook/react';
import { Chip } from './';

// Define the Meta object for the component
const meta = {
	title: 'Components/Chip',
	component: Chip,
	tags: ['autodocs'],
	argTypes: {
		children: {
			control: 'text',
			description: 'The content of the chip',
		},
		disabled: {
			control: 'boolean',
			description: 'Whether the chip is disabled',
			defaultValue: false,
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
			action: 'cancelled',
			description: 'Function called when the chip is cancelled (not needed for readonly chips)',
		},
		readonly: {
			control: 'boolean',
			description: 'If true, the chip will not have a remove icon',
			defaultValue: false,
		},
		variant: {
			options: ['primary', 'secondary', 'success', 'pending', 'danger', 'outline', 'secondary-outline', 'ghost', 'linkButton'],
			control: { type: 'select' },
			description: 'The visual style of the chip',
			defaultValue: 'secondary-outline',
		},
		onClick: {
			action: 'clicked',
			description: 'Function called when a readonly chip is clicked',
		},
	},
} satisfies Meta<typeof Chip>;

// Important: Export the meta object as the default export
export default meta;

// Define the Story type
type Story = StoryObj<typeof meta>;

export const Default: Story = {
	args: {
		children: 'Category',
		title: 'Remove Category filter',
		name: 'category',
		value: 'electronics',
		onCancel: () => console.log('Cancelled'),
	},
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
		onClick: () => console.log('Clicked on React tag'),
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
};

// @ts-ignore
export const ChipVariants: Story = {
	render: () => {
		return (
			<div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
				<Chip variant="secondary-outline" onCancel={() => console.log('Cancelled')}>
					Secondary Outline
				</Chip>

				<Chip variant="outline" onCancel={() => console.log('Cancelled')}>
					Outline
				</Chip>

				<Chip variant="primary" onCancel={() => console.log('Cancelled')}>
					Primary
				</Chip>

				<Chip variant="secondary" onCancel={() => console.log('Cancelled')}>
					Secondary
				</Chip>

				<Chip variant="success" onCancel={() => console.log('Cancelled')}>
					Success
				</Chip>

				<Chip variant="pending" onCancel={() => console.log('Cancelled')}>
					Pending
				</Chip>

				<Chip variant="danger" onCancel={() => console.log('Cancelled')}>
					Destructive
				</Chip>

				<Chip variant="ghost" onCancel={() => console.log('Cancelled')}>
					Ghost
				</Chip>

				<Chip variant="linkButton" onCancel={() => console.log('Cancelled')}>
					Link
				</Chip>

				<Chip variant="secondary-outline" readonly={true}>
					Readonly
				</Chip>
			</div>
		);
	},
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

// @ts-ignore
export const ArticleTags: Story = {
	render: () => {
		return (
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
						border: '1px solid #e0e0e0',
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
						<Chip
							readonly={true}
							variant="secondary-outline"
							onClick={() => console.log('TypeScript tag clicked')}
						>
							TypeScript
						</Chip>

						<Chip
							readonly={true}
							variant="secondary-outline"
							onClick={() => console.log('JavaScript tag clicked')}
						>
							JavaScript
						</Chip>

						<Chip
							readonly={true}
							variant="secondary-outline"
							onClick={() => console.log('Web Development tag clicked')}
						>
							Web Development
						</Chip>
					</div>
				</div>
			</div>
		);
	},
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

// @ts-ignore
export const FilterGroup: Story = {
	render: () => {
		const handleCancel = (filter: string) => {
			console.log(`Removed ${filter}`);
		};

		return (
			<div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap' }}>
				<Chip title="Remove Price filter" onCancel={() => handleCancel('Price: $10-$50')}>
					Price: $10-$50
				</Chip>
				<Chip title="Remove Color filter" onCancel={() => handleCancel('Color: Red')}>
					Color: Red
				</Chip>
				<Chip title="Remove Size filter" onCancel={() => handleCancel('Size: Medium')}>
					Size: Medium
				</Chip>
				<Chip title="Remove Category filter" onCancel={() => handleCancel('Category: Electronics')}>
					Category: Electronics
				</Chip>
				<Chip title="Remove Brand filter" disabled onCancel={() => handleCancel('Brand: Apple')}>
					Brand: Apple
				</Chip>
			</div>
		);
	},
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

// @ts-ignore
export const MixedChipGroup: Story = {
	render: () => {
		const handleCancel = (filter: string) => {
			console.log(`Removed ${filter}`);
		};

		const handleClick = (tag: string) => {
			console.log(`Clicked on ${tag} tag`);
		};

		return (
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
						backgroundColor: '#f5f5f5',
						borderRadius: '4px',
					}}
				>
					<div style={{ display: 'flex', alignItems: 'center', marginRight: '8px' }}>
						<strong>Categories:</strong>
					</div>

					<Chip readonly variant="outline" onClick={() => handleClick('Clothing')}>
						Clothing
					</Chip>

					<Chip readonly variant="outline" onClick={() => handleClick('Accessories')}>
						Accessories
					</Chip>

					<div style={{ display: 'flex', alignItems: 'center', margin: '0 8px' }}>
						<strong>Active Filters:</strong>
					</div>

					<Chip title="Remove Size filter" onCancel={() => handleCancel('Size: Medium')}>
						Size: Medium
					</Chip>

					<Chip title="Remove Color filter" onCancel={() => handleCancel('Color: Blue')}>
						Color: Blue
					</Chip>
				</div>
			</div>
		);
	},
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
