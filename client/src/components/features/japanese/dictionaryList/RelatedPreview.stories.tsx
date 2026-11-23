import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, within } from 'storybook/test';
import { RelatedPreview } from './RelatedPreview';

const meta = {
	title: 'Features/Japanese/RelatedPreview',
	component: RelatedPreview,
	parameters: { layout: 'padded' },
	args: {
		title: 'Words with 人',
		noun: 'words',
		total: 2398,
		isPending: false,
		isError: false,
		isEmpty: false,
		emptyMessage: 'No words use this kanji yet.',
		controller: {
			id: 'related-preview-modal',
			dialogRef: { current: null },
			isOpen: false,
			isRendered: false,
			open: fn(),
			close: fn(),
		},
		children: (
			<ul lang="ja">
				<li>人間</li>
				<li>人口</li>
				<li>大人</li>
			</ul>
		),
	},
} satisfies Meta<typeof RelatedPreview>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Loaded: Story = {
	play: async ({ canvasElement, args }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('heading', { level: 2, name: /Words with 人/ })).toBeVisible();
		const seeAll = canvas.getByRole('button', { name: 'See all 2,398 words' });
		seeAll.click();
		await expect(args.controller.open).toHaveBeenCalled();
	},
};

export const Empty: Story = {
	args: { isEmpty: true, total: 0 },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('No words use this kanji yet.')).toBeVisible();
	},
};

export const Failed: Story = {
	args: { isError: true },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('status')).toHaveTextContent('could not be loaded');
	},
};
