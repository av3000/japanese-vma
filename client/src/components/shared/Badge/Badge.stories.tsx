import type { Meta, StoryObj } from '@storybook/react';
import { Button } from '../Button/Button';
import Badge, { BadgeWrapper } from './';

const meta: Meta<typeof Badge> = {
	component: Badge,
	tags: ['autodocs'],
	argTypes: {
		variant: {
			options: ['neutral', 'danger'],
			control: { type: 'radio' },
		},
		isPositioned: {
			control: { type: 'boolean' },
		},
	},
};

export default meta;
type Story = StoryObj<typeof Badge>;

// Basic badge examples
export const Default: Story = {
	render: (args) => <Badge {...args}>42</Badge>,
};

export const Neutral: Story = {
	render: (args) => <Badge {...args}>42</Badge>,
	args: {
		variant: 'neutral',
	},
};

export const Danger: Story = {
	render: (args) => <Badge {...args}>42</Badge>,
	args: {
		variant: 'danger',
	},
};

export const EmptyDot: Story = {
	render: (args) => <Badge {...args} />,
};

// Usage examples
export const NotificationExamples: Story = {
	render: () => (
		<div style={{ display: 'flex', alignItems: 'center', gap: '24px' }}>
			<BadgeWrapper badgeContent={3}>
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path
						d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.89 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"
						fill="currentColor"
					/>
				</svg>
			</BadgeWrapper>

			<BadgeWrapper badgeContent={''} badgeProps={{ variant: 'danger' }}>
				<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
					<path
						d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"
						fill="currentColor"
					/>
				</svg>
			</BadgeWrapper>
		</div>
	),
};

export const InlineExamples: Story = {
	render: () => (
		<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
			<span>Messages</span>
			<Badge variant="danger">5</Badge>
			<span style={{ marginLeft: '16px' }}>Updates</span>
			<Badge variant="neutral">12</Badge>
		</div>
	),
};

export const StatusExamples: Story = {
	render: () => (
		<div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
			<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
				<span>Status indicators:</span>
				<div style={{ display: 'flex', alignItems: 'center', gap: '16px' }}>
					<div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
						<Badge variant="danger"></Badge>
						<span>Urgent</span>
					</div>
					<div style={{ display: 'flex', alignItems: 'center', gap: '4px' }}>
						<Badge variant="neutral"></Badge>
						<span>Normal</span>
					</div>
				</div>
			</div>

			<div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
				<div
					style={{
						width: '32px',
						height: '32px',
						borderRadius: '50%',
						backgroundColor: '#ccc',
						position: 'relative',
					}}
				>
					<Badge
						isPositioned
						variant="neutral"
						style={{
							border: '2px solid white',
							bottom: '0',
							top: 'auto',
						}}
					/>
				</div>
				<span>John Doe (online)</span>
			</div>
		</div>
	),
};

export const WithGhostButton: Story = {
	render: () => (
		<div style={{ display: 'flex', flexDirection: 'column', gap: '16px' }}>
			<div>
				<h3>Badge with Ghost Button</h3>
				<div style={{ display: 'flex', gap: '16px', marginTop: '8px' }}>
					<BadgeWrapper badgeContent={3} badgeProps={{ variant: 'danger' }}>
						<Button
							variant="ghost"
							onClick={(): void => {
								console.log('notifications button clicked');
							}}
						>
							Notifications
						</Button>
					</BadgeWrapper>

					<BadgeWrapper badgeContent={5} badgeProps={{ variant: 'neutral' }}>
						<Button
							variant="ghost"
							onClick={(): void => {
								console.log('messages button clicked');
							}}
						>
							Messages
						</Button>
					</BadgeWrapper>

					<BadgeWrapper badgeContent={''} badgeProps={{ variant: 'danger' }}>
						<Button
							variant="ghost"
							onClick={(): void => {
								console.log('updates button clicked');
							}}
						>
							Updates
						</Button>
					</BadgeWrapper>
				</div>
			</div>
		</div>
	),
};
