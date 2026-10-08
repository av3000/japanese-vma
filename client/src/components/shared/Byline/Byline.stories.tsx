import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { PUBLICITY } from '@/api/publicity';
import { VisibilityCue } from '@/components/shared/VisibilityCue';
import { Byline } from './';

const meta = {
	title: 'Shared/Byline',
	component: Byline,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: { name: 'Aki Tanaka', date: '2026-05-25T10:00:00+00:00', views: 1234 },
} satisfies Meta<typeof Byline>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('Aki Tanaka')).toBeVisible();
		await expect(canvas.getByText('1,234 views')).toBeVisible();
	},
};

export const JapaneseName: Story = { args: { name: '田中太郎' } };

/** Long names wrap inside the line; they never push the date off screen. */
export const LongName: Story = {
	args: { name: 'Wolfeschlegelsteinhausenbergerdorff'.repeat(3), views: 98765432 },
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};

export const UnknownAuthor: Story = { args: { name: '', views: 0 } };

/** An Imported Article credits its source instead of the account that imported it. */
export const ImportedSource: Story = {
	args: { source: 'NHK News', name: 'Content Import' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('NHK News')).toBeVisible();
		await expect(canvas.queryByText('Content Import')).not.toBeInTheDocument();
	},
};

/** The owner's second meta line: the visibility cue reads the same in greyscale. */
export const WithVisibilityCues: Story = {
	render: (args) => (
		<div style={{ display: 'grid', gap: 'var(--spacing-xs)' }}>
			<Byline {...args} />
			<div style={{ display: 'flex', gap: 'var(--spacing-xs)' }}>
				<VisibilityCue publicity={PUBLICITY.PUBLIC} />
				<VisibilityCue publicity={PUBLICITY.PRIVATE} />
			</div>
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('Public')).toBeVisible();
		await expect(canvas.getByText('Private')).toBeVisible();
	},
};
