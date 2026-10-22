import type { Meta, StoryObj } from '@storybook/react-vite';
import { Link } from '../Link';
import { linkSizes, linkWeights } from './types';

/**
 * One story: every prop is a control. `to` renders a router link; set `linkUrl` to an absolute URL
 * (and clear `to`) for the external link with its icon.
 */
const meta = {
	title: 'Shared/Link',
	component: Link,
	args: {
		to: '/articles',
		children: 'All articles',
		size: 'md',
		weight: 'regular',
		textLink: false,
		richTextLink: false,
		hasMinHeight: false,
		hideExternalIcon: false,
	},
	argTypes: {
		size: { control: 'inline-radio', options: linkSizes },
		weight: { control: 'inline-radio', options: linkWeights },
		to: { control: 'text' },
		linkUrl: { control: 'text' },
		title: { control: 'text' },
		target: { control: 'inline-radio', options: [undefined, '_blank'] },
	},
} satisfies Meta<typeof Link>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Playground: Story = {};
