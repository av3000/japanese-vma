import type { Meta, StoryObj } from '@storybook/react-vite';
import { Link } from '../Link';

const meta = {
	title: 'Shared/Link',
	component: Link,
} satisfies Meta<typeof Link>;

export default meta;
type Story = StoryObj<typeof meta>;

export const SetUrlViaRoute: Story = {
	render: (args) => <Link {...args}>Some link</Link>,
	args: {
		route: {
			externalRoute: '/catalogues',
		},
		linkUrl: '/catalogues',
	},
};

export const SetUrlViaToSimple: Story = {
	render: (args) => <Link {...args}>Some link</Link>,
	args: {
		to: '/articles',
	},
};

export const SetUrlViaToWithState: Story = {
	render: (args) => <Link {...args}>Some link</Link>,
	args: {
		to: '/catalogues',
		state: {
			externalRoute: '/catalogues',
		},
	},
};

export const SetUrlViaLinkUrl: Story = {
	render: (args) => <Link {...args}>Some link</Link>,
	args: {
		linkUrl: 'https://google.com',
	},
};

export const HasTarget: Story = {
	render: (args) => <Link {...args}>Some link</Link>,
	args: {
		linkUrl: 'https://google.com',
		target: '_blank',
	},
};

export const HasTitle: Story = {
	render: (args) => <Link {...args}>Some link</Link>,
	args: {
		to: '/articles',
		title: 'Links title shown on hover',
	},
};

export const HasTextLinkStyles: Story = {
	render: (args) => <Link {...args}>Some link</Link>,
	args: {
		to: '/articles',
		textLink: true,
	},
};

export const HasRichTextLinkStyles: Story = {
	render: (args) => <Link {...args}>Some link</Link>,
	args: {
		to: '/articles',
		richTextLink: true,
	},
};

export const HasMinHeightStyles: Story = {
	render: (args) => <Link {...args}>Min height to increase click area</Link>,
	args: {
		to: '/articles',
		hasMinHeight: true,
	},
};

export const IsColorDefault: Story = {
	render: (args) => <Link {...args}>Some link</Link>,
	args: {
		to: '/articles',
		color: 'default',
	},
};

export const IsSizeSm: Story = {
	render: (args) => <Link {...args}>Some link</Link>,
	args: {
		to: '/articles',
		size: 'sm',
	},
};

export const IsSizeMd: Story = {
	render: (args) => <Link {...args}>Some link</Link>,
	args: {
		to: '/articles',
		size: 'md',
	},
};

export const IsSizeLg: Story = {
	render: (args) => <Link {...args}>Some link</Link>,
	args: {
		to: '/articles',
		size: 'lg',
	},
};

export const IsWeightRegular: Story = {
	render: (args) => <Link {...args}>Some link</Link>,
	args: {
		to: '/articles',
		weight: 'regular',
	},
};

export const IsWeightBold: Story = {
	render: (args) => <Link {...args}>Some link</Link>,
	args: {
		to: '/articles',
		weight: 'bold',
	},
};
