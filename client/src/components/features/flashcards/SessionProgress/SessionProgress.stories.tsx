import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { SessionProgress } from './index';

const meta = {
	title: 'Features/Flashcards/SessionProgress',
	component: SessionProgress,
	parameters: { layout: 'padded' },
	args: { current: 1, answered: 0, total: 20, correct: 0 },
} satisfies Meta<typeof SessionProgress>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Card 1 of 20, nothing answered: the bar is empty. */
export const Start: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText(/of 20/)).toHaveTextContent('Card 1 of 20');
		await expect(canvas.getByText('0 correct')).toBeVisible();
		await expect(canvas.getByRole('progressbar', { name: 'Session progress' })).toHaveAttribute(
			'aria-valuenow',
			'0',
		);
	},
};

/** The bar fills by cards answered, not by the card on screen. */
export const Middle: Story = {
	args: { current: 7, answered: 6, correct: 5 },
	play: async ({ canvasElement }) => {
		const bar = within(canvasElement).getByRole('progressbar', { name: 'Session progress' });

		await expect(bar).toHaveAttribute('aria-valuenow', '6');
		await expect(bar).toHaveAttribute('aria-valuetext', '6 of 20 answered');
	},
};

/** On the last card, 19 answered: nearly full, but not full until this one is graded. */
export const Last: Story = {
	args: { current: 20, answered: 19, correct: 14 },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText(/of 20/)).toHaveTextContent('Card 20 of 20');
		await expect(canvas.getByRole('progressbar')).toHaveAttribute('aria-valuetext', '19 of 20 answered');
	},
};

/** The last card graded: the count never runs past the total. */
export const Finished: Story = {
	args: { current: 21, answered: 21, correct: 17 },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText(/of 20/)).toHaveTextContent('Card 20 of 20');
		await expect(canvas.getByRole('progressbar')).toHaveAttribute('aria-valuenow', '20');
	},
};

/** A one-card deck. */
export const OneCard: Story = {
	args: { current: 1, answered: 0, total: 1, correct: 0 },
};

/** An empty deck: no divide by zero, an empty bar. */
export const NoCards: Story = {
	args: { current: 0, answered: 0, total: 0, correct: 0 },
};

export const Mobile: Story = {
	args: { current: 100, answered: 99, total: 100, correct: 87 },
	globals: { viewport: { value: 'xs' } },
};
