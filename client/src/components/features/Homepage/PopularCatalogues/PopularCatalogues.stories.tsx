import type { Meta, StoryObj } from '@storybook/react';
import { popularCatalogues } from '../fixtures';
import { PopularCataloguesView } from './';

const meta = {
	title: 'Features/Homepage/PopularCatalogues',
	component: PopularCataloguesView,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: { status: 'success', catalogues: popularCatalogues, total: 37, onRetry: () => undefined },
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '36rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof PopularCataloguesView>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Includes a long Japanese title, a single-item catalogue and one without engagement counts. */
export const Loaded: Story = {};

export const Loading: Story = { args: { status: 'pending', catalogues: [], total: undefined } };

export const Empty: Story = { args: { catalogues: [], total: 0 } };

export const ErrorState: Story = { name: 'Error', args: { status: 'error', catalogues: [], total: undefined } };

export const MobileViewport: Story = { parameters: { viewport: { defaultViewport: 'mobile1' } } };
