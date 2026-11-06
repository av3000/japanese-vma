import type { Meta, StoryObj } from '@storybook/react-vite';
import { LikeIcon } from '.';
import { expect } from 'storybook/test';
import { Button } from '@/components/shared/Button';

/**
 * The like icon comes from the theme's `likeOff` / `likeOn` slots, so switch **Theme** and **Scheme** in
 * the toolbar to see each pair. The state is never colour alone: the shape changes, and the button
 * carries `aria-pressed`.
 */
const meta = {
	title: 'Shared/LikeIcon',
	component: LikeIcon,
	args: { liked: false, tone: 'state', size: 'sm' },
	argTypes: {
		tone: { control: 'inline-radio', options: ['state', 'count'] },
		size: { control: 'inline-radio', options: ['sm', 'md', 'lg'] },
	},
} satisfies Meta<typeof LikeIcon>;

export default meta;

type Story = StoryObj<typeof meta>;

const row = { display: 'flex', flexWrap: 'wrap', gap: '1rem', alignItems: 'center' } as const;

export const Playground: Story = {};

/** The three places it appears: a button not liked, a button liked, and a likes count on a card. */
export const InContext: Story = {
	render: () => (
		<div style={row}>
			<Button variant="outline" aria-pressed={false}>
				<LikeIcon liked={false} />
				Like · 12
			</Button>
			<Button variant="outline" aria-pressed>
				<LikeIcon liked />
				Like · 13
			</Button>
			<Button variant="ghost" size="sm" aria-pressed aria-label="Unlike this comment">
				<LikeIcon liked />
			</Button>
			<span style={{ display: 'inline-flex', gap: '0.25rem', alignItems: 'center' }}>
				<LikeIcon tone="count" />
				128
			</span>
		</div>
	),
	play: async ({ canvasElement }) => {
		const states = [...canvasElement.querySelectorAll('[data-icon-slot="like"]')].map((el) =>
			el.getAttribute('data-state'),
		);
		await expect(states).toEqual(['off', 'on', 'on', 'count']);
	},
};
