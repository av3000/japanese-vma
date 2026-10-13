import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, within } from 'storybook/test';
import { PAGE_LOADING_FAMILIES, PageLoading, type PageLoadingFamily } from './';

const meta = {
	title: 'Shared/PageLoading',
	component: PageLoading,
	parameters: { layout: 'padded' },
	args: { family: 'generic' },
	argTypes: {
		family: { control: 'select', options: PAGE_LOADING_FAMILIES },
	},
} satisfies Meta<typeof PageLoading>;

export default meta;

type Story = StoryObj<typeof meta>;

const expectPending = async (canvasElement: HTMLElement, label = 'Loading page.') => {
	await expect(within(canvasElement).getByRole('status')).toHaveTextContent(label);
};

export const Playground: Story = {
	play: async ({ canvasElement }) => {
		await expectPending(canvasElement);
	},
};

/** The placeholder of a list route, such as Articles or Catalogues: filters, a summary and cards. */
export const List: Story = {
	args: { family: 'list' },
	play: async ({ canvasElement }) => {
		await expectPending(canvasElement);
	},
};

/** A detail route: back link, title, meta, a hero block and text lines. */
export const Detail: Story = {
	args: { family: 'detail' },
	play: async ({ canvasElement }) => {
		await expectPending(canvasElement);
	},
};

/** A create or edit route: a title, three fields and a submit button. */
export const Form: Story = {
	args: { family: 'form' },
	play: async ({ canvasElement }) => {
		await expectPending(canvasElement);
	},
};

export const Dashboard: Story = {
	args: { family: 'dashboard' },
	play: async ({ canvasElement }) => {
		await expectPending(canvasElement);
	},
};

export const Generic: Story = {
	args: { family: 'generic' },
	play: async ({ canvasElement }) => {
		await expectPending(canvasElement);
	},
};

/** Each family side by side. Only the status text is announced; the blocks are hidden from assistive technology. */
export const AllFamilies: Story = {
	render: () => (
		<div style={{ display: 'grid', gap: '2rem' }}>
			{PAGE_LOADING_FAMILIES.map((family: PageLoadingFamily) => (
				<PageLoading key={family} family={family} label={`Loading ${family} page.`} />
			))}
		</div>
	),
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		for (const family of PAGE_LOADING_FAMILIES) {
			await expect(canvas.getByText(`Loading ${family} page.`)).toBeVisible();
		}
	},
};

/** A route can pass its own placeholder, such as the article detail layout, inside the same pending region. */
export const CustomVisual: Story = {
	args: {
		label: 'Loading article.',
		visual: (
			<div aria-hidden="true" style={{ height: '8rem', background: 'var(--color-neutral-200)' }}>
				Route-specific placeholder
			</div>
		),
	},
	play: async ({ canvasElement }) => {
		await expectPending(canvasElement, 'Loading article.');
	},
};

export const OnPhone: Story = {
	globals: { viewport: { value: 'xs' } },
	args: { family: 'list' },
	play: async ({ canvasElement }) => {
		await expectPending(canvasElement);
	},
};
