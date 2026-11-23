import { useRef } from 'react';
import type { Meta, StoryObj } from '@storybook/react-vite';
import { expect, userEvent, waitFor, within } from 'storybook/test';
import { Button } from '@/components/shared/Button';
import { useModal } from '@/hooks/useModal';
import { Drawer } from './';

const DrawerDemo = ({ label }: { label: string }) => {
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const modal = useModal(dialogRef, { transitionMs: 0 });

	return (
		<>
			<Button variant="secondary" onClick={modal.open}>
				Open drawer
			</Button>
			<Drawer modal={modal} label={label}>
				<div style={{ padding: 'var(--spacing-md)' }}>
					<p>Full-height content.</p>
					<Button variant="outline" onClick={modal.close}>
						Close
					</Button>
				</div>
			</Drawer>
		</>
	);
};

const meta = {
	title: 'Shared/Drawer',
	component: DrawerDemo,
	args: { label: 'Example drawer' },
	parameters: { layout: 'fullscreen' },
} satisfies Meta<typeof DrawerDemo>;

export default meta;

type Story = StoryObj<typeof meta>;

/** Opens as a modal dialog and returns focus to its trigger when closed. */
export const Default: Story = {
	play: async ({ canvasElement }) => {
		const canvas = within(canvasElement);
		const trigger = canvas.getByRole('button', { name: 'Open drawer' });

		await userEvent.click(trigger);
		const dialog = await waitFor(() => {
			const element = canvasElement.querySelector('dialog');
			expect(element?.open).toBe(true);
			return element as HTMLDialogElement;
		});
		await expect(dialog).toHaveAccessibleName('Example drawer');

		await userEvent.click(within(dialog).getByRole('button', { name: 'Close' }));
		await waitFor(() => expect(dialog.open).toBe(false));
		await waitFor(() => expect(trigger).toHaveFocus());
	},
};

/** Escape closes the drawer, releases the page's scroll lock and returns focus to the trigger. */
export const OpensAndCloses: Story = {
	play: async ({ canvasElement }) => {
		const trigger = within(canvasElement).getByRole('button', { name: 'Open drawer' });

		await userEvent.click(trigger);
		const dialog = await within(document.body).findByRole('dialog', { name: 'Example drawer' });
		await waitFor(() => expect(dialog).toHaveProperty('open', true));
		await expect(dialog).toBeVisible();
		// The scroll lock fixes the body in place while the drawer is open.
		await waitFor(() => expect(document.body.style.position).toBe('fixed'));

		// The browser turns a real Escape press into `cancel`; user-event's synthetic Escape never reaches a native dialog.
		dialog.dispatchEvent(new Event('cancel', { cancelable: true }));
		await waitFor(() => expect(dialog).toHaveProperty('open', false));
		await waitFor(() => expect(trigger).toHaveFocus());
		await waitFor(() => expect(document.body.style.position).toBe(''));
	},
};
