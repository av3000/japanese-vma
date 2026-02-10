import type { Meta, StoryObj } from '@storybook/react';
import { Icon } from '.';

const meta: Meta<typeof Icon> = {
	component: Icon,
	tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof Icon>;

export const Default: Story = {
	args: {
		name: 'user',
	},
};

export const SizeSm: Story = {
	args: {
		name: 'user',
		size: 'sm',
	},
};

export const SizeMd: Story = {
	args: {
		name: 'user',
		size: 'md',
	},
};

export const SizeLg: Story = {
	args: {
		name: 'user',
		size: 'lg',
	},
};

export const MobileSizeSm: Story = {
	args: {
		name: 'user',
		size: 'lg',
		mobileSize: 'sm',
	},
};

export const MobileSizeMd: Story = {
	args: {
		name: 'user',
		size: 'lg',
		mobileSize: 'md',
	},
};

export const MobileSizeLg: Story = {
	args: {
		name: 'user',
		size: 'md',
		mobileSize: 'lg',
	},
};

export const Rotate90: Story = {
	args: {
		name: 'chevron',
		size: 'md',
		rotate: '90',
	},
};

export const Rotate180: Story = {
	args: {
		name: 'chevron',
		size: 'md',
		rotate: '180',
	},
};

export const Rotate270: Story = {
	args: {
		name: 'chevron',
		size: 'md',
		rotate: '270',
	},
};
