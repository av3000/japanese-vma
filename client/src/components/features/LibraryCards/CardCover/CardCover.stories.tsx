import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import { LevelBadge } from '@/components/shared/LevelBadge';
import { processingStatusPill, StatusPill } from '@/components/shared/StatusPill';
import { CATALOGUE_TYPE_LABELS } from '@/shared/constants/catalogues';
import { articleCoverGlyph, catalogueCoverGlyph } from '../coverRule';
import { CardCover, CoverChip } from './';

const meta = {
	title: 'Features/LibraryCards/CardCover',
	component: CardCover,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: {
		glyph: articleCoverGlyph('毎年交流してきたお年よりだけど'),
		badge: <LevelBadge level="N4" size="sm" aria-hidden="true" />,
	},
	decorators: [
		(Story) => (
			<div style={{ display: 'grid', maxWidth: '20rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof CardCover>;

export default meta;

type Story = StoryObj<typeof meta>;

/** The first kanji of the title, with the dominant level as a badge. */
export const Article: Story = {
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).getByText('毎')).toHaveAttribute('aria-hidden', 'true');
	},
};

/** A title without kanji falls back to 記; no level, so no badge. */
export const ArticleWithoutKanji: Story = {
	args: { glyph: articleCoverGlyph('いっしょにあそぼう'), badge: undefined },
};

/** The status pill sits on the cover without hiding the glyph, and stays readable. */
export const ArticleProcessing: Story = {
	args: {
		glyph: articleCoverGlyph('台風14号、週末に九州へ接近のおそれ'),
		badge: undefined,
		status: <StatusPill {...processingStatusPill('processing')} />,
	},
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('Processing')).toBeVisible();
		await expect(canvas.getByText('台')).toBeVisible();
	},
};

/** Every custom catalogue type, from `CATALOGUE_TYPE_LABELS`, with its glyph and item count. */
export const EveryCatalogueType: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '1rem' }}>
			{Object.entries(CATALOGUE_TYPE_LABELS).map(([type, label]) => (
				<CardCover
					key={type}
					glyph={catalogueCoverGlyph(Number(type))}
					badge={<CoverChip>{label}</CoverChip>}
					caption="128 items"
				/>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		for (const glyph of ['部', '字', '語', '文', '記']) {
			await expect(canvas.getByText(glyph)).toBeVisible();
		}
		await expect(canvas.getAllByText('128 items')).toHaveLength(Object.keys(CATALOGUE_TYPE_LABELS).length);
	},
};

export const EmptyCatalogue: Story = {
	args: { glyph: catalogueCoverGlyph(5), badge: <CoverChip>Radicals</CoverChip>, caption: '0 items' },
};
