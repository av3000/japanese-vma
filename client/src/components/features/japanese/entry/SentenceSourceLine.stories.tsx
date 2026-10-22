import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { sentenceSource } from '../entryValues';
import { SentenceSourceLine } from './SentenceSourceLine';

const meta = {
	title: 'Features/Japanese/Entry/SentenceSourceLine',
	component: SentenceSourceLine,
	parameters: { layout: 'padded' },
	args: { source: { kind: 'tatoeba', entry: '4777', href: 'https://tatoeba.org/eng/sentences/show/4777' } },
} satisfies Meta<typeof SentenceSourceLine>;

export default meta;

type Story = StoryObj<typeof meta>;

/** An import: a link to the sentence on Tatoeba, opened without a referrer. */
export const Tatoeba: Story = {
	play: async ({ canvasElement }) => {
		const link = within(canvasElement).getByRole('link', { name: 'Tatoeba #4777' });
		await expect(link).toHaveAttribute('href', 'https://tatoeba.org/eng/sentences/show/4777');
		await expect(link).toHaveAttribute('rel', 'noopener noreferrer');
	},
};

/** The viewer's own sentence. */
export const Own: Story = {
	args: { source: { kind: 'own' } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Written by you')).toBeVisible();
	},
};

/** Another member's sentence; the payload carries no author name yet (#549). */
export const Member: Story = {
	args: { source: { kind: 'member' } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Written by a member')).toBeVisible();
	},
};

/** An entry id that needs encoding in the URL. */
export const OddEntryId: Story = {
	args: {
		source: sentenceSource({ user_id: null, tatoeba_entry: 'a b/1' }, null) ?? { kind: 'member' },
	},
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('link')).toHaveAttribute(
			'href',
			'https://tatoeba.org/eng/sentences/show/a%20b%2F1',
		);
	},
};

export const Mobile: Story = {
	globals: { viewport: { value: 'xs' } },
};
