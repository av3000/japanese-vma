import type { Meta, StoryObj } from '@storybook/react';
import { expect, within } from '@storybook/test';
import type { CatalogueResourceTypeLabel } from '@/api/generated/model';
import { libraryCatalogues, makeCatalogue } from '@/components/features/Homepage/fixtures';
import { CATALOGUE_TYPE_OPTIONS, type CustomCatalogueType } from '@/shared/constants/catalogues';
import { CatalogueCard } from './';

const meta = {
	title: 'Features/LibraryCards/CatalogueCard',
	component: CatalogueCard,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: { catalogue: libraryCatalogues.kanji },
	decorators: [
		(Story) => (
			<div style={{ maxWidth: '22rem' }}>
				<Story />
			</div>
		),
	],
} satisfies Meta<typeof CatalogueCard>;

export default meta;

type Story = StoryObj<typeof meta>;

/** The label the backend sends for each custom type; a Record, so a new type fails typecheck here. */
const BACKEND_TYPE_LABELS: Record<CustomCatalogueType, CatalogueResourceTypeLabel> = {
	5: 'Radicals',
	6: 'Kanji',
	7: 'Words',
	8: 'Sentences',
	9: 'Articles',
};

/** A Kanji catalogue: 字 on the cover, the type and item count, and a kanji JLPT bar. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('link', { name: 'N3 kanji for news reading' })).toHaveAttribute(
			'href',
			'/catalogues/catalogue-kanji',
		);
		await expect(canvas.getByText('128 items')).toBeVisible();
		await expect(canvas.getByRole('img', { name: /^Mostly N3: / })).toBeVisible();
		await expect(canvas.getByRole('list', { name: 'Stats' })).toHaveTextContent('18 downloads');
	},
};

/** Long title, description and owner name; a Sentences catalogue's bar counts words. */
export const LongEverything: Story = {
	args: { catalogue: libraryCatalogues.longest },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByRole('img', { name: /^Mostly N3 words: / })).toBeVisible();
		await expect(canvas.getByText('1,203 items')).toBeVisible();
	},
};

/** Radicals with no items: no JLPT data, no description, no tags, zero engagement. */
export const EmptyCatalogue: Story = {
	args: { catalogue: libraryCatalogues.empty },
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getByText('0 items')).toBeVisible();
		await expect(canvas.queryByRole('img')).not.toBeInTheDocument();
	},
};

/** Every word is unassigned today, so a Words catalogue shows no bar rather than one grey segment. */
export const WordsWithoutLevels: Story = {
	args: { catalogue: libraryCatalogues.wordsUnassigned },
	play: async ({ canvasElement }) => {
		await expect(within(canvasElement).queryByRole('img')).not.toBeInTheDocument();
	},
};

/** One card per custom type, from `CATALOGUE_TYPE_OPTIONS`. */
export const EveryType: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '1rem' }}>
			{CATALOGUE_TYPE_OPTIONS.map(({ value }) => (
				<CatalogueCard
					key={value}
					catalogue={makeCatalogue({
						uuid: `catalogue-${value}`,
						type: value,
						type_label: BACKEND_TYPE_LABELS[value],
						title: `${BACKEND_TYPE_LABELS[value]} catalogue`,
					})}
				/>
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		await expect(canvas.getAllByRole('link')).toHaveLength(CATALOGUE_TYPE_OPTIONS.length);
		for (const glyph of ['部', '字', '語', '文', '記']) {
			await expect(canvas.getByText(glyph)).toBeVisible();
		}
	},
};

export const Mobile: Story = {
	args: { catalogue: libraryCatalogues.longest },
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};
