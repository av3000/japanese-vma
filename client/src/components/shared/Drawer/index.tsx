import * as React from 'react';
import classNames from 'classnames';
import type { ModalController } from '@/hooks/useModal';
import styles from './Drawer.module.css';

export interface DrawerProps {
	/** From `useModal`: owns `showModal()`, Escape, scroll lock and focus return. */
	modal: ModalController;
	/** Accessible name of the dialog. */
	label: string;
	children: React.ReactNode;
	className?: string;
}

/**
 * A full-height sheet on the native `<dialog>`, for mobile navigation and search. Opened with
 * `showModal()`, it traps focus and makes the page inert; `useModal` adds Escape, the scroll lock
 * and focus return to the trigger. Unlike `DialogModal` it brings no close button or padded
 * sections: the content lays out its own top bar.
 */
export const Drawer: React.FC<DrawerProps> = ({ modal, label, children, className }) => (
	<dialog ref={modal.dialogRef} id={modal.id} aria-label={label} className={classNames(styles.drawer, className)}>
		{children}
	</dialog>
);
