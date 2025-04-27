// Chip.stories.tsx
import React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { Chip } from './';

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
			description: 'Function called when the chip is cancelled',
		},
	},
} satisfies Meta<typeof Chip>;

export default meta;

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
## Basic Chip

The Chip component is used to display selected filters, tags, or other small pieces of information
that can be removed by the user.

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

export const Disabled: Story = {
	args: {
		children: 'Category',
		title: 'Cannot remove this filter',
		disabled: true,
		onCancel: () => console.log('Cancelled'),
	},
	parameters: {
		docs: {
			description: {
				story: `
## Disabled Chip

Chips can be disabled when removal is not allowed.

\`\`\`tsx
<Chip 
  title="Cannot remove this filter" 
  disabled={true}
  onCancel={() => {}}
>
  Category
</Chip>
\`\`\`
        `,
			},
		},
	},
};

export const LongContent: Story = {
	args: {
		children: 'This is a very long chip content that will be truncated with ellipsis',
		title: 'Remove long filter',
		onCancel: () => console.log('Cancelled'),
	},
	parameters: {
		docs: {
			description: {
				story: `
## Chip with Long Content

When the content is too long, it will be truncated with an ellipsis.
        `,
			},
		},
	},
};

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

\`\`\`tsx
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
\`\`\`
        `,
			},
		},
	},
};

export const FilterBar: Story = {
	render: () => {
		const handleCancel = (filter: string) => {
			console.log(`Removed ${filter}`);
		};

		const handleClearAll = () => {
			console.log('Cleared all filters');
		};

		return (
			<div
				style={{
					display: 'flex',
					gap: '8px',
					alignItems: 'center',
					padding: '12px',
					backgroundColor: '#f5f5f5',
					borderRadius: '4px',
				}}
			>
				<span style={{ marginRight: '8px', fontWeight: 500 }}>Filters:</span>
				<div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', flex: 1 }}>
					<Chip title="Remove Price filter" onCancel={() => handleCancel('Price: $10-$50')}>
						Price: $10-$50
					</Chip>
					<Chip title="Remove Color filter" onCancel={() => handleCancel('Color: Red')}>
						Color: Red
					</Chip>
					<Chip title="Remove Size filter" onCancel={() => handleCancel('Size: Medium')}>
						Size: Medium
					</Chip>
				</div>
				<button
					onClick={handleClearAll}
					style={{
						background: 'transparent',
						border: 'none',
						cursor: 'pointer',
						color: '#1976d2',
						fontSize: '14px',
						padding: '4px 8px',
						borderRadius: '4px',
						marginLeft: 'auto',
					}}
				>
					Clear All
				</button>
			</div>
		);
	},
	parameters: {
		docs: {
			description: {
				story: `
## Filter Bar

Chips are often used in a filter bar with a "Clear All" button.

\`\`\`tsx
<div style={{ 
  display: 'flex', 
  gap: '8px', 
  alignItems: 'center',
  padding: '12px',
  backgroundColor: '#f5f5f5',
  borderRadius: '4px'
}}>
  <span style={{ marginRight: '8px', fontWeight: 500 }}>Filters:</span>
  <div style={{ display: 'flex', gap: '8px', flexWrap: 'wrap', flex: 1 }}>
    <Chip title="Remove Price filter" onCancel={() => handleCancel('Price: $10-$50')}>
      Price: $10-$50
    </Chip>
    <Chip title="Remove Color filter" onCancel={() => handleCancel('Color: Red')}>
      Color: Red
    </Chip>
    <Chip title="Remove Size filter" onCancel={() => handleCancel('Size: Medium')}>
      Size: Medium
    </Chip>
  </div>
  <button onClick={handleClearAll}>Clear All</button>
</div>
\`\`\`
        `,
			},
		},
	},
};

export const TagsInput: Story = {
	render: () => {
		const [tags, setTags] = React.useState(['React', 'TypeScript', 'Storybook']);
		const [inputValue, setInputValue] = React.useState('');

		const handleRemoveTag = (tag: string) => {
			setTags(tags.filter((t) => t !== tag));
		};

		const handleKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
			if (event.key === 'Enter' && inputValue.trim()) {
				setTags([...tags, inputValue.trim()]);
				setInputValue('');
				event.preventDefault();
			}
		};

		return (
			<div
				style={{
					border: '1px solid #ccc',
					borderRadius: '4px',
					padding: '8px',
					display: 'flex',
					flexWrap: 'wrap',
					gap: '8px',
					alignItems: 'center',
				}}
			>
				{tags.map((tag) => (
					<Chip key={tag} title={`Remove ${tag} tag`} onCancel={() => handleRemoveTag(tag)}>
						{tag}
					</Chip>
				))}
				<input
					type="text"
					value={inputValue}
					onChange={(e) => setInputValue(e.target.value)}
					onKeyDown={handleKeyDown}
					placeholder="Add tag and press Enter"
					style={{
						border: 'none',
						outline: 'none',
						padding: '8px 0',
						flexGrow: 1,
						minWidth: '120px',
					}}
				/>
			</div>
		);
	},
	parameters: {
		docs: {
			description: {
				story: `
## Tags Input

Chips can be used to create a tags input where users can add and remove tags.

\`\`\`tsx
const [tags, setTags] = React.useState(['React', 'TypeScript', 'Storybook']);
const [inputValue, setInputValue] = React.useState('');

const handleRemoveTag = (tag: string) => {
  setTags(tags.filter(t => t !== tag));
};

const handleKeyDown = (event: React.KeyboardEvent<HTMLInputElement>) => {
  if (event.key === 'Enter' && inputValue.trim()) {
    setTags([...tags, inputValue.trim()]);
    setInputValue('');
    event.preventDefault();
  }
};

return (
  <div style={{ 
    border: '1px solid #ccc', 
    borderRadius: '4px',
    padding: '8px',
    display: 'flex',
    flexWrap: 'wrap',
    gap: '8px',
    alignItems: 'center'
  }}>
    {tags.map(tag => (
      <Chip 
        key={tag} 
        title={\`Remove \${tag} tag\`} 
        onCancel={() => handleRemoveTag(tag)}
      >
        {tag}
      </Chip>
    ))}
    <input
      type="text"
      value={inputValue}
      onChange={(e) => setInputValue(e.target.value)}
      onKeyDown={handleKeyDown}
      placeholder="Add tag and press Enter"
      style={{
        border: 'none',
        outline: 'none',
        padding: '8px 0',
        flexGrow: 1,
        minWidth: '120px'
      }}
    />
  </div>
);
\`\`\`
        `,
			},
		},
	},
};
