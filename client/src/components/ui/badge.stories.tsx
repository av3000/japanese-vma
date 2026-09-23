import type { Meta, StoryObj } from '@storybook/react';
import { Badge } from './badge';
import storyStyles from './stories.module.css';

const meta = {
	title: 'UI/Badge',
	component: Badge,
	tags: ['autodocs'],
	argTypes: {
		variant: {
			options: ['default', 'secondary', 'success', 'destructive', 'outline', 'ghost', 'link', 'pending'],
			control: { type: 'select' },
		},
		children: {
			control: 'text',
		},
		asChild: {
			control: 'boolean',
		},
		isOnlyIcon: {
			control: 'boolean',
		},
	},
	args: {
		variant: 'default',
		children: 'New',
		asChild: false,
		isOnlyIcon: false,
	},
} satisfies Meta<typeof Badge>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {};

Default.parameters = {
	docs: {
		description: {
			story: `
**When to use**

- Use **Badge** for compact status, counts, or state indicators (e.g., "Pending", "3 new").
- For interactive filtering or removable tags, prefer **Chip**.
`,
		},
	},
};

export const Variants: Story = {
	render: () => (
		<div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', alignItems: 'center' }}>
			<Badge variant="default">Default</Badge>
			<Badge variant="secondary">Secondary</Badge>
			<Badge variant="success">Success</Badge>
			<Badge variant="success">+5</Badge>
			<Badge variant="pending">Pending</Badge>
			<Badge variant="destructive">Destructive</Badge>
			<Badge variant="outline">Outline</Badge>
			<Badge variant="ghost">Ghost</Badge>
			<Badge variant="link">Link</Badge>
		</div>
	),
	parameters: {
		docs: {
			description: {
				story: `
Badges are small, inline labels for status/metadata.

\`\`\`tsx
<Badge variant="success">Completed</Badge>
<Badge variant="pending">Processing</Badge>
\`\`\`
`,
			},
		},
	},
};

export const LongText: Story = {
	args: {
		children: 'This is a longer badge label',
		variant: 'secondary',
	},
};

export const WithIcon: Story = {
	render: () => (
		<Badge variant="outline">
			<svg viewBox="0 0 24 24" aria-hidden="true">
				<path
					fill="currentColor"
					d="M12 2a10 10 0 1 0 .001 20.001A10 10 0 0 0 12 2Zm1 15h-2v-2h2v2Zm0-4h-2V7h2v6Z"
				/>
			</svg>
			Info
		</Badge>
	),
};

export const AsLink: Story = {
	render: () => (
		<Badge asChild variant="link">
			<button onClick={(e) => e.preventDefault()}>View details</button>
		</Badge>
	),
	parameters: {
		docs: {
			description: {
				story: `
Use \`asChild\` to render the badge as another element (e.g. an anchor) while keeping badge styling.
`,
			},
		},
	},
};

export const NotificationIndicator: Story = {
	render: () => (
		<div style={{ display: 'flex', gap: 16, flexWrap: 'wrap', alignItems: 'center' }}>
			<button type="button" className={storyStyles.triggerButton}>
				Notifications
				<Badge aria-label="New notifications" className={storyStyles.dot} variant="destructive" />
			</button>

			<button type="button" className={storyStyles.triggerButton}>
				Messages
				<Badge aria-label="New messages" className={storyStyles.dot} variant="pending" />
			</button>
		</div>
	),
	parameters: {
		docs: {
			description: {
				story: `
Attach a small dot badge to a button for unread/new indicators.

\`\`\`tsx
<button className={styles.trigger}> {/* position: relative */}
  Notifications
  <Badge className={styles.dot} variant="destructive" /> {/* position: absolute; top/right: -4px; 10px round */}
</button>
\`\`\`
`,
			},
		},
	},
};

export const IconOnly: Story = {
	render: () => (
		<div style={{ display: 'flex', gap: 12, flexWrap: 'wrap', alignItems: 'center' }}>
			<Badge isOnlyIcon variant="default" aria-label="Default icon badge">
				<svg viewBox="0 0 24 24" aria-hidden="true">
					<path
						fill="currentColor"
						d="M12 2a10 10 0 1 0 .001 20.001A10 10 0 0 0 12 2Zm1 15h-2v-2h2v2Zm0-4h-2V7h2v6Z"
					/>
				</svg>
			</Badge>
			<Badge isOnlyIcon variant="secondary" aria-label="Secondary icon badge">
				<svg viewBox="0 0 24 24" aria-hidden="true">
					<path
						fill="currentColor"
						d="M12 2a10 10 0 1 0 .001 20.001A10 10 0 0 0 12 2Zm1 15h-2v-2h2v2Zm0-4h-2V7h2v6Z"
					/>
				</svg>
			</Badge>
			<Badge isOnlyIcon variant="success" aria-label="Success icon badge">
				<svg viewBox="0 0 24 24" aria-hidden="true">
					<path fill="currentColor" d="M9 16.2 4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4z" />
				</svg>
			</Badge>
			<Badge isOnlyIcon variant="pending" aria-label="Pending icon badge">
				<svg viewBox="0 0 24 24" aria-hidden="true">
					<path fill="currentColor" d="M12 8v5l4 2 .8-1.3-3.3-1.7V8H12Z" />
				</svg>
			</Badge>
			<Badge isOnlyIcon variant="destructive" aria-label="Destructive icon badge">
				<svg viewBox="0 0 24 24" aria-hidden="true">
					<path
						fill="currentColor"
						d="M12 2a10 10 0 1 0 .001 20.001A10 10 0 0 0 12 2Zm5 13.6L15.6 17 12 13.4 8.4 17 7 15.6 10.6 12 7 8.4 8.4 7 12 10.6 15.6 7 17 8.4 13.4 12 17 15.6Z"
					/>
				</svg>
			</Badge>
		</div>
	),
	parameters: {
		docs: {
			description: {
				story: `
Use \`isOnlyIcon\` when the badge should be a perfectly round icon-only control (no text).

\`\`\`tsx
<Badge isOnlyIcon variant="success" aria-label="Completed">
  <CheckIcon />
</Badge>
\`\`\`
`,
			},
		},
	},
};
