import type { Meta, StoryObj } from '@storybook/react-vite';
import { Button } from '.';
import { expect, fn, userEvent, within } from 'storybook/test';
import { Icon } from '@/components/shared/Icon';
import { buttonSizes, buttonVariants } from './types';

const meta = {
	title: 'Shared/Button',
	component: Button,
	args: {
		children: 'Some button',
		variant: 'primary',
		size: 'md',
		onClick: fn(),
	},
	argTypes: {
		size: { options: buttonSizes, control: { type: 'radio' } },
		variant: { options: buttonVariants, control: { type: 'radio' } },
		disabled: { control: { type: 'boolean' } },
		isLoading: { control: { type: 'boolean' } },
		isFullWidth: { control: { type: 'boolean' } },
		hasOnlyIcon: { control: { type: 'boolean' } },
		hasNoPaddingX: { control: { type: 'boolean' } },
	},
} satisfies Meta<typeof Button>;

export default meta;

type Story = StoryObj<typeof meta>;

const row = { display: 'flex', flexWrap: 'wrap', gap: '0.75rem', alignItems: 'center' } as const;

export const Playground: Story = {
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'Some button' })).toBeVisible();
	},
};

export const AllVariants: Story = {
	render: (args) => (
		<div style={row}>
			{buttonVariants.map((variant) => (
				<Button key={variant} {...args} variant={variant}>
					{variant}
				</Button>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const variant of buttonVariants) {
			await expect(canvas.getByRole('button', { name: variant })).toBeVisible();
		}
	},
};

export const Sizes: Story = {
	render: (args) => (
		<div style={row}>
			{buttonSizes.map((size) => (
				<Button key={size} {...args} size={size}>
					Size {size}
				</Button>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const size of buttonSizes) {
			await expect(canvas.getByRole('button', { name: `Size ${size}` })).toBeVisible();
		}
	},
};

/** While loading the button is disabled and shows a spinner, and its label stays its accessible name. */
export const Loading: Story = {
	args: { isLoading: true, children: 'Saving' },
	play: async ({ canvasElement }) => {
		const button = within(canvasElement).getByRole('button', { name: 'Saving' });

		await expect(button).toBeDisabled();
	},
};

export const Disabled: Story = {
	render: (args) => (
		<div style={row}>
			{buttonVariants.map((variant) => (
				<Button key={variant} {...args} variant={variant} disabled>
					{variant}
				</Button>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		for (const button of within(canvasElement).getAllByRole('button')) {
			await expect(button).toBeDisabled();
		}
	},
};

/** An icon-only button has no text, so it needs an `aria-label`. */
export const IconOnly: Story = {
	render: (args) => (
		<div style={row}>
			{buttonSizes.map((size) => (
				<Button key={size} {...args} size={size} hasOnlyIcon aria-label={`Profile, ${size}`}>
					<Icon name="user" size="md" />
				</Button>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const size of buttonSizes) {
			await expect(canvas.getByRole('button', { name: `Profile, ${size}` })).toBeVisible();
		}
	},
};

export const WithIcon: Story = {
	render: (args) => (
		<div style={row}>
			<Button {...args}>
				<Icon name="user" size="md" />
				<span>My profile</span>
			</Button>
			<Button {...args} variant="ghost" hasNoPaddingX>
				<Icon name="user" size="md" />
				<span>No horizontal padding</span>
			</Button>
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('button', { name: 'My profile' })).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'No horizontal padding' })).toBeVisible();
	},
};

export const FullWidth: Story = {
	args: { isFullWidth: true },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: 'Some button' })).toBeVisible();
	},
};

/** Buttons joined into one control. The position says which corners are rounded. */
export const CtaGroup: Story = {
	args: { variant: 'secondary', size: 'sm' },
	render: (args) => (
		<div role="group" aria-label="Quick actions" style={{ display: 'inline-flex' }}>
			<Button {...args} ctaGroupPos="left">
				<Icon name="user" size="md" />
				<span>My profile</span>
			</Button>
			<Button {...args} ctaGroupPos="center">
				<Icon name="chevron" size="md" />
				<span>Filters</span>
			</Button>
			<Button {...args} ctaGroupPos="right">
				<Icon name="plus" size="md" />
				<span>New article</span>
			</Button>
		</div>
	),
	play: async ({ canvasElement }) => {
		const group = within(canvasElement).getByRole('group', { name: 'Quick actions' });

		await expect(within(group).getAllByRole('button')).toHaveLength(3);
	},
};

/** With `href` the button renders as a plain `<a>`, for links that leave the app. */
export const AsAnchor: Story = {
	args: { href: 'https://example.com/', children: 'Open example.com' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('link', { name: 'Open example.com' })).toHaveAttribute(
			'href',
			'https://example.com/',
		);
	},
};

/** With `to` the button renders as a router link, for navigation inside the app. */
export const AsRouterLink: Story = {
	args: { to: '/articles', children: 'Go to articles' },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('link', { name: 'Go to articles' })).toHaveAttribute(
			'href',
			'/articles',
		);
	},
};

/** `onClick` fires on a click, and not when the button is disabled or loading. */
export const Clicks: Story = {
	render: (args) => (
		<div style={row}>
			<Button {...args}>Enabled</Button>
			<Button {...args} disabled>
				Disabled
			</Button>
			<Button {...args} isLoading>
				Loading
			</Button>
			<Button {...args} hasOnlyIcon aria-label="Profile">
				<Icon name="user" size="md" />
			</Button>
		</div>
	),
	play: async ({ args, canvasElement }) => {
		const canvas = within(canvasElement);
		// Disabled buttons set `pointer-events: none`, which user-event refuses to click by default.
		const user = userEvent.setup({ pointerEventsCheck: 0 });

		await user.click(canvas.getByRole('button', { name: 'Enabled' }));
		await expect(args.onClick).toHaveBeenCalledTimes(1);

		await user.click(canvas.getByRole('button', { name: 'Disabled' }));
		await expect(args.onClick).toHaveBeenCalledTimes(1);

		await user.click(canvas.getByRole('button', { name: 'Loading' }));
		await expect(args.onClick).toHaveBeenCalledTimes(1);

		// Icon-only: named by its aria-label, and it clicks like any other button.
		await user.click(canvas.getByRole('button', { name: 'Profile' }));
		await expect(args.onClick).toHaveBeenCalledTimes(2);
	},
};
