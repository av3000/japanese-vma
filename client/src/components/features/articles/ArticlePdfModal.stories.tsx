import { useRef, type ComponentProps } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, fn, userEvent, waitFor, within } from 'storybook/test';
import { Button } from '@/components/shared/Button';
import { useModal } from '@/hooks/useModal';
import { ArticlePdfModal } from './ArticlePdfModal';

type ModalProps = ComponentProps<typeof ArticlePdfModal>;

/** Owns the dialog the way a parent does: its ref and open state come from `useModal`. */
const WithTrigger = (props: Omit<ModalProps, 'controller'>) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const controller = useModal(dialogRef);

	return (
		<>
			<Button variant="secondary" onClick={controller.open}>
				Export PDF
			</Button>
			<ArticlePdfModal {...props} controller={controller} />
		</>
	);
};

const openDialog = async (canvasElement: HTMLElement, name = 'Generate PDF') => {
	await userEvent.click(within(canvasElement).getByRole('button', { name: 'Export PDF' }));

	const dialog = within(await within(document.body).findByRole('dialog', { name }));
	// The panel fades in; wait until it is fully there before reading or clicking it.
	await waitFor(() => expect(dialog.getByRole('button', { name: 'Vocabulary List' })).toBeVisible());

	return dialog;
};

const meta = {
	title: 'Features/Articles/ArticlePdfModal',
	component: ArticlePdfModal,
	parameters: { layout: 'centered' },
	// The render below owns the controller; this placeholder only satisfies the props type.
	args: {
		controller: {
			id: 'storybook-article-pdf',
			dialogRef: { current: null },
			isOpen: false,
			isRendered: false,
			open: fn(),
			close: fn(),
		},
		onDownload: fn(),
		isDownloadEnabled: true,
	},
	argTypes: { controller: { control: false }, onDownload: { control: false } },
	render: ({ controller: _controller, ...args }) => <WithTrigger {...args} />,
} satisfies Meta<typeof ArticlePdfModal>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Each list is one action; the parent reports which kind it is generating. */
export const Default: Story = {
	play: async ({ canvasElement, args }) => {
		const dialog = await openDialog(canvasElement);
		await expect(dialog.getByRole('button', { name: 'Kanji List' })).toBeEnabled();

		await userEvent.click(dialog.getByRole('button', { name: 'Kanji List' }));
		await expect(args.onDownload).toHaveBeenLastCalledWith('kanji');

		await userEvent.click(dialog.getByRole('button', { name: 'Vocabulary List' }));
		await expect(args.onDownload).toHaveBeenLastCalledWith('words');
		await expect(args.onDownload).toHaveBeenCalledTimes(2);
	},
};

/** The close button closes the dialog without downloading and returns focus to the control that opened it. */
export const Closes: Story = {
	play: async ({ canvasElement, args }) => {
		const trigger = within(canvasElement).getByRole('button', { name: 'Export PDF' });
		const dialog = await openDialog(canvasElement);

		await userEvent.click(dialog.getByRole('button', { name: 'Close dialog' }));
		await waitFor(() => expect(within(document.body).queryByRole('dialog')).not.toBeInTheDocument());
		await waitFor(() => expect(trigger).toHaveFocus());
		await expect(args.onDownload).not.toHaveBeenCalled();
	},
};

/** Without kanji attached (still processing, or none found) the kanji list cannot be made. */
export const KanjiListUnavailable: Story = {
	args: { isDownloadEnabled: false },
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement);
		await expect(dialog.getByRole('button', { name: 'Kanji List' })).toBeDisabled();
		await expect(dialog.getByRole('button', { name: 'Vocabulary List' })).toBeEnabled();
	},
};

/** The kanji list is being generated: its button is disabled while the other kind stays available. */
export const Generating: Story = {
	args: { pendingType: 'kanji' },
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement);
		await expect(dialog.getByRole('button', { name: 'Kanji List' })).toBeDisabled();
		await expect(dialog.getByRole('button', { name: 'Vocabulary List' })).toBeEnabled();
	},
};

export const Failed: Story = {
	args: { errorMessage: 'We could not generate the PDF. Try again in a moment.' },
	play: async ({ canvasElement }) => {
		const dialog = await openDialog(canvasElement);
		await expect(dialog.getByRole('alert')).toHaveTextContent('We could not generate the PDF.');
	},
};

/** A catalogue-length title wraps inside the dialog. */
export const LongTitle: Story = {
	args: {
		title: 'Generate a PDF of the kanji and vocabulary lists for 新宿駅の新しい改札が完成しました、東口と西口をつなぐ通路',
		ariaLabel: 'Generate PDF',
	},
	play: async ({ canvasElement }) => {
		await openDialog(canvasElement);
		const root = canvasElement.ownerDocument.documentElement;
		await expect(root.scrollWidth).toBeLessThanOrEqual(root.clientWidth);
	},
};
