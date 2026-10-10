import * as React from 'react';
import type { Meta, StoryObj } from '@storybook/react';
import { expect, userEvent, within } from '@storybook/test';
import { Pagination } from './';

/** Holds the page in state so the story can be clicked through. */
const Controlled: React.FC<{ initialPage: number; pageCount: number }> = ({ initialPage, pageCount }) => {
	const [page, setPage] = React.useState(initialPage);

	return <Pagination page={page} pageCount={pageCount} onPageChange={setPage} label="Kanji pages" />;
};

const meta = {
	title: 'Shared/Pagination',
	component: Pagination,
	tags: ['autodocs'],
	parameters: { layout: 'padded' },
	args: { page: 1, pageCount: 9, onPageChange: () => {}, label: 'Kanji pages' },
} satisfies Meta<typeof Pagination>;

export default meta;

type Story = StoryObj<typeof meta>;

export const FirstPage: Story = {
	render: () => <Controlled initialPage={1} pageCount={9} />,
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);

		await expect(canvas.getByRole('button', { name: 'Previous page' })).toBeDisabled();
		await userEvent.click(canvas.getByRole('button', { name: 'Page 2' }));
		await expect(canvas.getByRole('button', { name: 'Page 2' })).toHaveAttribute('aria-current', 'page');
	},
};

export const MiddleOfMany: Story = { render: () => <Controlled initialPage={12} pageCount={25} /> };

export const FewPages: Story = { render: () => <Controlled initialPage={2} pageCount={4} /> };

export const Mobile: Story = {
	render: () => <Controlled initialPage={12} pageCount={25} />,
	parameters: { viewport: { defaultViewport: 'mobile1' } },
};
