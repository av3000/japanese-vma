// Badge.stories.tsx
import type { Meta, StoryObj } from '@storybook/react';
import { Button } from '@/components/shared/Button';
import AvatarWithStatus from '../AvatarWithStatus';
import { Badge } from './Badge';
import AnimatedBadgeDemo from './BadgeAnimatedDemo';

const meta: Meta<typeof Badge> = {
	component: Badge,
	title: 'Components/Badge',
	tags: ['autodocs'],
	argTypes: {
		color: {
			options: ['primary', 'secondary', 'error', 'success', 'warning'],
			control: { type: 'radio' },
			description: 'The color of the badge',
			defaultValue: 'primary',
		},
		anchorOrigin: {
			description: 'The anchor of the badge',
			defaultValue: { vertical: 'top', horizontal: 'right' },
		},
		invisible: {
			control: { type: 'boolean' },
			description: 'If true, the badge is invisible',
			defaultValue: false,
		},
		max: {
			control: { type: 'number' },
			description: 'Max count to show',
		},
		showZero: {
			control: { type: 'boolean' },
			description: 'Controls whether the badge is hidden when badgeContent is zero',
			defaultValue: false,
		},
		variant: {
			options: ['standard', 'dot'],
			control: { type: 'radio' },
			description: 'The variant to use',
			defaultValue: 'standard',
		},
		standalone: {
			control: { type: 'boolean' },
			description: 'If true, the badge is standalone without children',
			defaultValue: false,
		},
		animated: {
			control: { type: 'boolean' },
			description: 'If true, the badge will animate when content changes',
			defaultValue: false,
		},
	},
};

export default meta;
type Story = StoryObj<typeof Badge>;

export const Default: Story = {
	args: {
		badgeContent: 4,
		children: <Button variant="secondary-outline">Notifications</Button>,
	},
	parameters: {
		docs: {
			description: {
				story: `
## Basic Badge Usage

The Badge component displays a small badge to the top-right corner of its child component.

\`\`\`tsx
import { Badge } from '@/components/shared/Badge';
import { Button } from '@/components/shared/Button';

// Basic usage
<Badge badgeContent={4}>
  <Button variant="secondary-outline">Notifications</Button>
</Badge>
\`\`\`
        `,
			},
		},
	},
};

export const Colors: Story = {
	render: () => (
		<div style={{ display: 'flex', gap: '16px' }}>
			<Badge badgeContent={4} color="primary">
				<Button variant="secondary-outline">Primary</Button>
			</Badge>

			<Badge badgeContent={4} color="secondary">
				<Button variant="secondary-outline">Secondary</Button>
			</Badge>

			<Badge badgeContent={4} color="error">
				<Button variant="secondary-outline">Error</Button>
			</Badge>

			<Badge badgeContent={4} color="success">
				<Button variant="secondary-outline">Success</Button>
			</Badge>

			<Badge badgeContent={4} color="warning">
				<Button variant="secondary-outline">Warning</Button>
			</Badge>
		</div>
	),
	parameters: {
		docs: {
			description: {
				story: `
## Color Variants

The Badge component supports different color variants:

- \`primary\` - For general notifications
- \`secondary\` - For lesser importance notifications
- \`error\` - For error notifications
- \`warning\` - For warning notifications
- \`success\` - For success notifications
        `,
			},
		},
	},
};

export const Dot: Story = {
	args: {
		variant: 'dot',
		children: <Button variant="secondary-outline">Notifications</Button>,
	},
	parameters: {
		docs: {
			description: {
				story: `
## Dot Badge

For simple status indicators, you can use the dot variant.

\`\`\`tsx
<Badge variant="dot">
  <Button variant="secondary-outline">Notifications</Button>
</Badge>
\`\`\`
        `,
			},
		},
	},
};

export const MaxValue: Story = {
	args: {
		badgeContent: 142,
		max: 99,
		children: <Button variant="secondary-outline">Messages</Button>,
	},
	parameters: {
		docs: {
			description: {
				story: `
## Maximum Value

When you need to cap the displayed value, use the max prop.

\`\`\`tsx
// Will display "99+" instead of 142
<Badge badgeContent={142} max={99}>
  <Button variant="secondary-outline">Messages</Button>
</Badge>
\`\`\`
        `,
			},
		},
	},
};

export const BadgeContent: Story = {
	render: () => (
		<div style={{ display: 'flex', gap: '16px' }}>
			<Badge badgeContent={1}>
				<Button variant="secondary-outline">Single Digit</Button>
			</Badge>

			<Badge badgeContent={10}>
				<Button variant="secondary-outline">Double Digit</Button>
			</Badge>

			<Badge badgeContent={100}>
				<Button variant="secondary-outline">Triple Digit</Button>
			</Badge>
		</div>
	),
	parameters: {
		docs: {
			description: {
				story: `
## Badge Content Size

The badge adapts its shape based on content:
- Always circular/pill-shaped with rounded ends
- Expands horizontally to fit longer content
        `,
			},
		},
	},
};

export const PositionExamples: Story = {
	render: () => (
		<div style={{ display: 'flex', flexDirection: 'column', gap: '32px' }}>
			<div>
				<h3>Badge Positioning</h3>
				<div style={{ display: 'flex', gap: '24px', marginTop: '16px', flexWrap: 'wrap' }}>
					<div>
						<h4>Top Right (Default)</h4>
						<div style={{ position: 'relative' }}>
							<Badge
								badgeContent={1}
								color="error"
								anchorOrigin={{ vertical: 'top', horizontal: 'right' }}
							>
								<Button variant="secondary-outline">Notifications</Button>
							</Badge>
						</div>
					</div>

					<div>
						<h4>Top Left</h4>
						<div style={{ position: 'relative' }}>
							<Badge
								badgeContent={2}
								color="error"
								anchorOrigin={{ vertical: 'top', horizontal: 'left' }}
							>
								<Button variant="secondary-outline">Notifications</Button>
							</Badge>
						</div>
					</div>

					<div>
						<h4>Bottom Right</h4>
						<div style={{ position: 'relative' }}>
							<Badge
								badgeContent={3}
								color="error"
								anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
							>
								<Button variant="secondary-outline">Notifications</Button>
							</Badge>
						</div>
					</div>

					<div>
						<h4>Bottom Left</h4>
						<div style={{ position: 'relative' }}>
							<Badge
								badgeContent={4}
								color="error"
								anchorOrigin={{ vertical: 'bottom', horizontal: 'left' }}
							>
								<Button variant="secondary-outline">Notifications</Button>
							</Badge>
						</div>
					</div>
				</div>
			</div>
		</div>
	),
	parameters: {
		docs: {
			description: {
				story: `
## Badge Positioning

The badge can be positioned at any of the four corners of its child component.

\`\`\`tsx
// Top-left position
<Badge 
  badgeContent={4} 
  anchorOrigin={{ vertical: 'top', horizontal: 'left' }}
>
  <Button variant="secondary-outline">Notifications</Button>
</Badge>

// Bottom-right position
<Badge 
  badgeContent={4} 
  anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
>
  <Button variant="secondary-outline">Notifications</Button>
</Badge>
\`\`\`
        `,
			},
		},
	},
};

export const StatusIndicators: Story = {
	render: () => (
		<div style={{ display: 'flex', flexDirection: 'column', gap: '24px' }}>
			<div style={{ display: 'flex', alignItems: 'center', gap: '24px' }}>
				<AvatarWithStatus userId="1" linkTitle="JD" status="success" image={{ size180: null }} />

				<div style={{ position: 'relative', display: 'inline-block' }}>
					<div
						style={{
							width: '40px',
							height: '40px',
							borderRadius: '50%',
							backgroundColor: '#e0e0e0',
							display: 'flex',
							alignItems: 'center',
							justifyContent: 'center',
							fontSize: '16px',
							fontWeight: 'bold',
						}}
					>
						AB
					</div>
					<Badge
						variant="dot"
						color="error"
						anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
						style={{
							position: 'absolute',
							bottom: 0,
							right: 0,
							transform: 'translate(25%, 25%)',
						}}
					/>
				</div>
			</div>
		</div>
	),
	parameters: {
		docs: {
			description: {
				story: `
## Status Indicators

Badges can be used as status indicators for avatars and other components.

\`\`\`tsx
// Online status indicator
<div style={{ position: 'relative' }}>
  <Avatar>JD</Avatar>
  <Badge
    variant="dot"
    color="success"
    anchorOrigin={{ vertical: 'bottom', horizontal: 'right' }}
    standalone={true}
  />
</div>
\`\`\`
        `,
			},
		},
	},
};

export const AnimatedDemo: Story = {
	render: () => <AnimatedBadgeDemo />,
	parameters: {
		docs: {
			description: {
				story: `
## Animated Badge

The animated badge provides a "pop" animation when the badge value increases above 1.

\`\`\`tsx
// For an animated badge, add the animated prop
<Badge badgeContent={count} animated>
  <Button variant="secondary-outline">Notifications</Button>
</Badge>
\`\`\`

### Animation Behavior
- Badge appears with a scale animation when it first gets a value
- When the value increases to 2 or higher, a "pop" animation draws attention to the change
- Only animates when the value is meaningful (2 or higher)
        `,
			},
		},
	},
};
