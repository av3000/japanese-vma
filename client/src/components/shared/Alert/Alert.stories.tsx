import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, within } from 'storybook/test';
import { Button } from '@/components/shared/Button';
import { Alert, type AlertTone } from './';

/** `AlertTone` is a type, so the list lives here and the compiler checks it against the type. */
const ALERT_TONES = ['info', 'success', 'warning', 'danger'] as const satisfies readonly AlertTone[];

const meta = {
	title: 'Shared/Alert',
	component: Alert,
	parameters: { layout: 'padded' },
	args: { tone: 'info', children: 'Your changes were saved.' },
	argTypes: {
		tone: { control: 'inline-radio', options: ALERT_TONES },
	},
} satisfies Meta<typeof Alert>;

export default meta;

type Story = StoryObj<typeof meta>;

const stack = { display: 'flex', flexDirection: 'column', gap: '0.75rem' } as const;

export const Playground: Story = {
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('status')).toHaveTextContent('Your changes were saved.');
	},
};

/** Every tone. `danger` and `warning` announce as `alert`, the others as `status`. */
export const AllTones: Story = {
	render: () => (
		<div style={stack}>
			{ALERT_TONES.map((tone) => (
				<Alert key={tone} tone={tone}>
					This is a {tone} message.
				</Alert>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getAllByRole('alert')).toHaveLength(2);
		await expect(canvas.getAllByRole('status')).toHaveLength(2);
	},
};

export const WithHeading: Story = {
	args: { tone: 'success', heading: 'Article published', children: 'It is now visible to everyone.' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('Article published')).toBeVisible();
		await expect(canvas.getByText('It is now visible to everyone.')).toBeVisible();
	},
};

export const WithAction: Story = {
	args: {
		tone: 'danger',
		children: 'The article could not be saved.',
		actions: (
			<Button size="sm" variant="outline" onClick={fn()}>
				Try again
			</Button>
		),
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const retry = canvas.getByRole('button', { name: 'Try again' });

		await userEvent.click(retry);
		await expect(canvas.getByRole('alert')).toContainElement(retry);
	},
};

export const WithHeadingAndActions: Story = {
	args: {
		tone: 'warning',
		heading: 'This article is still being analysed',
		children: 'Kanji and words appear when the analysis finishes, usually within a minute.',
		actions: (
			<>
				<Button size="sm" variant="outline" onClick={fn()}>
					Refresh
				</Button>
				<Button size="sm" variant="ghost" onClick={fn()}>
					Dismiss
				</Button>
			</>
		),
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('alert')).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Refresh' })).toBeVisible();
		await expect(canvas.getByRole('button', { name: 'Dismiss' })).toBeVisible();
	},
};

/** An explicit `role` wins over the tone's default. */
export const RoleOverride: Story = {
	args: { tone: 'danger', role: 'status', children: 'Announced politely, though the tone is danger.' },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('status')).toBeVisible();
		await expect(canvas.queryByRole('alert')).not.toBeInTheDocument();
	},
};

/** Long Japanese text and a long heading wrap inside the alert on a phone. */
export const LongContent: Story = {
	globals: { viewport: { value: 'xs' } },
	args: {
		tone: 'info',
		heading: '新宿駅の新しい改札口についてのお知らせとご案内',
		children:
			'新宿駅では、利用者の混雑を緩和するために、新しい改札口が来月から使えるようになります。工事の期間中は、一部の出口が閉鎖されるため、時間に余裕を持ってお出かけください。',
		actions: (
			<Button size="sm" variant="outline" onClick={fn()}>
				詳しく見る
			</Button>
		),
	},
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('button', { name: '詳しく見る' })).toBeVisible();
	},
};
