import type { Meta, StoryObj } from '@storybook/react';
import { expect, userEvent, within } from '@storybook/test';
import { ArticleBody, ArticleTitle } from './';

const PARAGRAPHS = [
	'ロシアによる軍事侵攻が長期化する中、ウクライナでは21日、伝統衣装を着て1日を過ごす「ビシバンカの日」を迎え、多くの人が伝統衣装を着て街に繰り出し、ウクライナ人としての結束を再確認していました。',
	'「ビシバンカ」は幾何学模様や植物などの刺しゅうが施されたウクライナの伝統衣装で、毎年5月の第3木曜日は「ビシバンカの日」とされ、多くの人が伝統衣装を着て1日を過ごします。',
];
const TRANSLATION =
	'As the Russian invasion drags on, Ukraine marked Vyshyvanka Day on the 21st.\nMany people went out in traditional embroidered shirts to reaffirm their unity.';

/** About 2,000 characters, an unbroken 400-character run and a 300-character URL. */
const HOSTILE_BODY = [
	...Array.from({ length: 9 }, (_, index) => PARAGRAPHS[index % 2]),
	'あ'.repeat(400),
	`https://example.com/${'a'.repeat(280)}`,
].join('\n');

const meta = {
	title: 'Features/Articles/ArticleBody',
	component: ArticleBody,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	decorators: [
		(Story) => (
			<div style={{ maxWidth: 720 }}>
				<Story />
			</div>
		),
	],
	args: { contentJp: PARAGRAPHS.join('\n\n'), contentEn: TRANSLATION },
} satisfies Meta<typeof ArticleBody>;

export default meta;

type Story = StoryObj<typeof meta>;

export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const summary = canvas.getByText('English translation');

		await expect(canvas.queryByText(/Vyshyvanka Day/)).not.toBeVisible();
		await userEvent.click(summary);
		await expect(canvas.getByText(/Vyshyvanka Day/)).toBeVisible();
	},
};

export const WithoutTranslation: Story = { args: { contentEn: null } };

/** An Imported Article: the credit sits where the excerpt ends, before the translation. */
export const Imported: Story = {
	args: {
		attribution: (
			<aside
				aria-label="Article source"
				style={{ padding: 'var(--spacing-sm)', background: 'var(--color-neutral-100)' }}
			>
				This is an excerpt from <strong>NHK News</strong>.{' '}
				<a href="https://www3.nhk.or.jp/news/">Read the full article on NHK News</a>
			</aside>
		),
	},
};

/** No horizontal scroll at 360px: long runs and URLs wrap inside the column. */
export const HostileBodyMobile: Story = {
	args: { contentJp: HOSTILE_BODY },
	parameters: { viewport: { defaultViewport: 'mobile1' } },
	play: async ({ canvasElement }) => {
		await expect(canvasElement.scrollWidth).toBeLessThanOrEqual(canvasElement.clientWidth + 1);
	},
};

export const Title: Story = {
	render: () => (
		<ArticleTitle
			titleJp="ウクライナで「ビシバンカの日」市民が伝統衣装で結束を確認"
			titleEn="Ukrainians mark Vyshyvanka Day"
		/>
	),
};

/** A 255-character title with no spaces wraps inside the column. */
export const LongestTitleMobile: Story = {
	render: () => <ArticleTitle titleJp={'長'.repeat(255)} titleEn={'L'.repeat(255)} />,
	parameters: { viewport: { defaultViewport: 'mobile1' } },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByRole('heading', { level: 1 })).toBeVisible();
		await expect(canvasElement.scrollWidth).toBeLessThanOrEqual(canvasElement.clientWidth + 1);
	},
};
