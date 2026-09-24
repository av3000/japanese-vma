import type { Meta, StoryObj } from '@storybook/react';
import type { ArticleResourceJlptLevels } from '@/api/generated/model/articleResourceJlptLevels';
import { JlptBar } from './';

const typicalArticle: ArticleResourceJlptLevels = { n1: 3, n2: 6, n3: 15, n4: 8, n5: 12, uncommon: 2 };
const singleLevel: ArticleResourceJlptLevels = { n1: 0, n2: 0, n3: 0, n4: 0, n5: 9, uncommon: 0 };
const allZero: ArticleResourceJlptLevels = { n1: 0, n2: 0, n3: 0, n4: 0, n5: 0, uncommon: 0 };
const uncommonHeavy: ArticleResourceJlptLevels = { n1: 2, n2: 1, n3: 4, n4: 0, n5: 3, uncommon: 28 };
const tie: ArticleResourceJlptLevels = { n1: 0, n2: 10, n3: 0, n4: 4, n5: 10, uncommon: 0 };

const meta = {
	title: 'Components/JlptBar',
	component: JlptBar,
	tags: ['autodocs'],
	args: { levels: typicalArticle, size: 'default' },
	argTypes: {
		size: { control: 'inline-radio', options: ['default', 'compact'] },
	},
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '24rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof JlptBar>;

export default meta;

type Story = StoryObj<typeof meta>;

export const TypicalArticle: Story = {};

export const SingleLevel: Story = { args: { levels: singleLevel } };

/** Renders nothing: an article that is still processing has no counts yet. */
export const AllZero: Story = { args: { levels: allZero } };

export const UncommonHeavy: Story = { args: { levels: uncommonHeavy } };

/** N5 and N2 both have 10 kanji; the harder level (N2) is dominant. */
export const Tie: Story = { args: { levels: tie } };

export const CompactVsDefault: Story = {
	render: (args) => (
		<div style={{ display: 'grid', gap: '1rem' }}>
			<JlptBar {...args} size="default" />
			<JlptBar {...args} size="compact" />
		</div>
	),
};
