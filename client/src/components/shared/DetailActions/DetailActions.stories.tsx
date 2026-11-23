import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, within } from 'storybook/test';
import { Button } from '@/components/shared/Button';
import { StatusPill } from '@/components/shared/StatusPill';
import { DetailActionGroup, DetailActions } from './';

const LONG_LABEL = 'Download kanji and words as PDF';

const actionButton = (label: string) => (
	<Button key={label} variant="outline" isFullWidth onClick={fn()}>
		{label}
	</Button>
);

const meta = {
	title: 'Shared/DetailActions',
	component: DetailActionGroup,
	// The rail is a narrow column; 20rem is about what the detail layout gives it.
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '20rem' }}>
				<Story />
			</div>
		),
	],
	args: {
		heading: 'Your article',
		children: null,
	},
} satisfies Meta<typeof DetailActionGroup>;

export default meta;

type Story = StoryObj<typeof meta>;

export const OneAction: Story = {
	args: { children: actionButton('Edit article') },
	play: async ({ canvasElement }) => {
		const group = within(canvasElement).getByRole('region', { name: 'Your article' });

		await expect(group).toBeVisible();
		await expect(within(group).getAllByRole('button')).toHaveLength(1);
	},
};

export const TwoActions: Story = {
	args: { children: [actionButton('Edit article'), actionButton('Delete article')] },
	play: async ({ canvasElement }) => {
		const group = within(canvasElement).getByRole('region', { name: 'Your article' });

		await expect(within(group).getAllByRole('button')).toHaveLength(2);
	},
};

const FIVE_LABELS = ['Like · 3', 'Edit article', LONG_LABEL, 'Submit for review', 'Delete article'];

/** Five actions, one with a long label: each stays full width and the long label wraps. */
export const FiveActions: Story = {
	args: { children: FIVE_LABELS.map(actionButton) },
	play: async ({ canvasElement }) => {
		const group = within(canvasElement).getByRole('region', { name: 'Your article' });

		await expect(group).toBeVisible();
		for (const label of FIVE_LABELS) {
			await expect(within(group).getByRole('button', { name: label })).toBeVisible();
		}
		await expect(within(group).getAllByRole('button')).toHaveLength(5);
	},
};

/** Every action is visible, full width inside the group, and nothing scrolls sideways. */
const expectFiveActionsFit = async (canvasElement: HTMLElement) => {
	const group = within(canvasElement).getByRole('region', { name: 'Your article' });
	const groupBox = group.getBoundingClientRect();

	await expect(within(group).getAllByRole('button')).toHaveLength(5);
	for (const label of FIVE_LABELS) {
		const button = within(group).getByRole('button', { name: label });
		const box = button.getBoundingClientRect();

		await expect(button).toBeVisible();
		await expect(box.left).toBeGreaterThanOrEqual(groupBox.left - 1);
		await expect(box.right).toBeLessThanOrEqual(groupBox.right + 1);
	}
	await expect(document.documentElement.scrollWidth).toBeLessThanOrEqual(window.innerWidth);
};

/** The same five actions on a phone (360px), where the rail is the width of the screen. */
export const FiveActionsOnPhone: Story = {
	globals: { viewport: { value: 'xs' } },
	args: { children: FIVE_LABELS.map(actionButton) },
	play: async ({ canvasElement }) => {
		await expect(window.innerWidth).toBe(360);
		await expectFiveActionsFit(canvasElement);
	},
};

/** The same five actions in the rail on a wide desktop (1400px). */
export const FiveActionsWide: Story = {
	parameters: {
		viewport: {
			options: { wide: { name: 'wide (1400px)', styles: { width: '1400px', height: '900px' }, type: 'desktop' } },
		},
	},
	globals: { viewport: { value: 'wide' } },
	args: { children: FIVE_LABELS.map(actionButton) },
	play: async ({ canvasElement }) => {
		await expect(window.innerWidth).toBe(1400);
		await expectFiveActionsFit(canvasElement);
	},
};

/** `meta` shows between the heading and the actions, such as the current moderation status. */
export const WithMeta: Story = {
	args: {
		heading: 'Moderation',
		headingLevel: 3,
		meta: <StatusPill tone="warning" label="Approval: Pending" />,
		children: [actionButton('Approve'), actionButton('Reject')],
	},
	play: async ({ canvasElement }) => {
		const group = within(canvasElement).getByRole('region', { name: 'Moderation' });

		await expect(within(group).getByRole('heading', { level: 3, name: 'Moderation' })).toBeVisible();
		await expect(within(group).getByText('Approval: Pending')).toBeVisible();
	},
};

/** `DetailActions` is the bare stack, for a rail with no heading. */
export const BareStack: Story = {
	render: () => (
		<DetailActions>
			{actionButton('Edit article')}
			{actionButton(LONG_LABEL)}
		</DetailActions>
	),
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getAllByRole('button')).toHaveLength(2);
	},
};
