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

/** A member's sentence, credited by name through the shared byline. */
export const Member: Story = {
	args: { source: { kind: 'member', name: 'Kenji' } },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByText('Kenji')).toBeVisible();
		await expect(canvas.getByText(/by/)).toBeVisible();
	},
};

/** The viewer's own sentence reads the same way, with their name. */
export const Own: Story = {
	args: { source: { kind: 'own', name: 'Hanako' } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Hanako')).toBeVisible();
	},
};

/** A member's sentence whose payload has no author name falls back to a generic credit. */
export const MemberWithoutName: Story = {
	args: { source: { kind: 'member', name: null } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('Written by a member')).toBeVisible();
	},
};

/** An entry id that needs encoding in the URL. */
export const OddEntryId: Story = {
	args: {
		source: sentenceSource({ user_id: null, tatoeba_entry: 'a b/1' }, null) ?? { kind: 'member', name: null },
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
