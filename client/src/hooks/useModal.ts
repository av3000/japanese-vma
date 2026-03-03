import type { RefObject } from 'react';
import { useCallback, useEffect, useId, useRef, useState } from 'react';
import { useOnClickAway } from '@/hooks/useOnClickAway';
import { useScrollLock } from '@/hooks/useScrollLock';

export interface UseModalOptions {
	id?: string;
	onOpen?: () => void;
	onClose?: () => void;
	transitionMs?: number;
	closeOnEscape?: boolean;
}

export interface ModalController {
	id: string;
	dialogRef: RefObject<HTMLDialogElement | null>;
	isOpen: boolean;
	isRendered: boolean;
	open: () => void;
	close: () => void;
}

export const useModal = (
	dialogRef: RefObject<HTMLDialogElement | null>,
	{ id: idProp, onOpen, onClose, transitionMs = 400, closeOnEscape = true }: UseModalOptions = {},
): ModalController => {
	const reactId = useId();
	const id = idProp ?? `dialog-${reactId}`;
	const [isOpen, setIsOpen] = useState(false);
	const [isRendered, setIsRendered] = useState(false);
	const openTimeoutRef = useRef<number | null>(null);
	const closeTimeoutRef = useRef<number | null>(null);
	const activeElementRef = useRef<HTMLElement | null>(null);
	const setScrollLock = useScrollLock();

	const clearOpenTimeout = useCallback(() => {
		if (openTimeoutRef.current !== null) {
			window.clearTimeout(openTimeoutRef.current);
			openTimeoutRef.current = null;
		}
	}, []);

	const clearCloseTimeout = useCallback(() => {
		if (closeTimeoutRef.current !== null) {
			window.clearTimeout(closeTimeoutRef.current);
			closeTimeoutRef.current = null;
		}
	}, []);

	const restoreFocus = useCallback(() => {
		// Capture the element that was active before we opened the dialog.
		const target = activeElementRef.current;
		// Clear the ref so we don't focus a stale element later.
		activeElementRef.current = null;
		// Native <dialog> focus restoration is inconsistent across browsers.
		// We restore it ourselves for predictable accessibility behavior.
		if (target?.focus) {
			target.focus();
		}
	}, []);

	const open = useCallback(() => {
		clearOpenTimeout();
		clearCloseTimeout();
		setIsRendered(true);

		if (dialogRef.current?.open) return;

		// Store the focused element so we can restore it on close.
		activeElementRef.current = document.activeElement instanceof HTMLElement ? document.activeElement : null;

		// Delay opening slightly so the dialog can render before showModal.
		openTimeoutRef.current = window.setTimeout(() => {
			// Read the current dialog node when the timer fires.
			const node = dialogRef.current;
			// Bail out if the dialog is gone or already open.
			if (!node || node.open) return;
			// Mark the modal as open for UI state and aria attributes.
			setIsOpen(true);
			node.removeAttribute('inert');
			node.showModal();
			setScrollLock(true);
			onOpen?.();
		}, 10);
	}, [clearOpenTimeout, clearCloseTimeout, dialogRef, onOpen, setScrollLock]);

	const close = useCallback(() => {
		clearOpenTimeout();
		if (closeTimeoutRef.current !== null) {
			return;
		}
		setIsOpen(false);
		onClose?.();
		if (dialogRef.current?.open) {
			dialogRef.current.close();
			dialogRef.current.setAttribute('inert', '');
			setScrollLock(false);
		}

		// Delay unmounting so exit animations can finish.
		closeTimeoutRef.current = window.setTimeout(() => {
			// Remove the dialog from the tree after the transition.
			setIsRendered(false);
			// Restore focus to where the user was before opening.
			restoreFocus();
		}, transitionMs);
	}, [clearOpenTimeout, dialogRef, onClose, restoreFocus, setScrollLock, transitionMs]);

	useEffect(() => {
		const node = dialogRef.current;
		if (!node) return;
		node.setAttribute('inert', '');

		return () => {
			clearOpenTimeout();
			clearCloseTimeout();
			setScrollLock(false);
		};
	}, [clearOpenTimeout, clearCloseTimeout, dialogRef, setScrollLock]);

	useEffect(() => {
		const node = dialogRef.current;
		if (!node) return;

		const handleCancel = (event: Event) => {
			if (!closeOnEscape) {
				event.preventDefault();
				return;
			}

			event.preventDefault();
			close();
		};

		node.addEventListener('cancel', handleCancel);

		return () => {
			node.removeEventListener('cancel', handleCancel);
		};
	}, [close, closeOnEscape, dialogRef]);

	useOnClickAway(dialogRef, close, isOpen);

	return { id, dialogRef, isOpen, isRendered, open, close };
};
