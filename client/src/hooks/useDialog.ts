import type { RefObject } from 'react';
import { useCallback, useEffect, useRef, useState } from 'react';

export interface UseDialogOptions {
	onClose?: () => void;
	transitionMs?: number;
	closeOnEscape?: boolean;
	lockScroll?: boolean;
}

export interface UseDialogResponse {
	isOpen: boolean;
	isRendered: boolean;
	handleOpen: () => void;
	handleClose: () => void;
}

const getScrollbarWidth = () => window.innerWidth - document.documentElement.clientWidth;

export const useDialog = (
	dialogRef: RefObject<HTMLDialogElement | null>,
	{ onClose, transitionMs = 400, closeOnEscape = true, lockScroll = true }: UseDialogOptions = {},
): UseDialogResponse => {
	const [isOpen, setIsOpen] = useState(false);
	const [isRendered, setIsRendered] = useState(false);
	const closeTimeoutRef = useRef<number | null>(null);
	const bodyStylesRef = useRef<{ overflow: string; paddingRight: string } | null>(null);

	const clearCloseTimeout = useCallback(() => {
		if (closeTimeoutRef.current !== null) {
			window.clearTimeout(closeTimeoutRef.current);
			closeTimeoutRef.current = null;
		}
	}, []);

	const lockBodyScroll = useCallback(() => {
		if (!lockScroll) return;
		const body = document.body;
		if (!bodyStylesRef.current) {
			bodyStylesRef.current = {
				overflow: body.style.overflow,
				paddingRight: body.style.paddingRight,
			};
		}

		// Locking scroll keeps background content fixed while the dialog is open.
		const scrollbarWidth = getScrollbarWidth();
		body.style.overflow = 'hidden';
		// Align body padding with scrollbar width to prevent layout shift.
		body.style.paddingRight = scrollbarWidth > 0 ? `${scrollbarWidth}px` : body.style.paddingRight;
	}, [lockScroll]);

	const unlockBodyScroll = useCallback(() => {
		if (!lockScroll) return;
		const body = document.body;
		if (!bodyStylesRef.current) return;
		body.style.overflow = bodyStylesRef.current.overflow;
		body.style.paddingRight = bodyStylesRef.current.paddingRight;
		bodyStylesRef.current = null;
	}, [lockScroll]);

	const handleOpen = useCallback((): void => {
		clearCloseTimeout();
		setIsRendered(true);

		if (!dialogRef.current?.open) {
			setTimeout(() => {
				const node = dialogRef.current;
				if (!node) return;
				setIsOpen(true);
				node.removeAttribute('inert');
				node.showModal();
				lockBodyScroll();
			}, 10);
		}
	}, [clearCloseTimeout, dialogRef, lockBodyScroll]);

	const handleClose = useCallback((): void => {
		clearCloseTimeout();

		setIsOpen(false);
		if (dialogRef.current?.open) {
			dialogRef.current?.close();
			dialogRef.current?.setAttribute('inert', '');
			unlockBodyScroll();
		}

		// Keep the dialog mounted for the duration of the CSS transition.
		closeTimeoutRef.current = window.setTimeout(() => {
			setIsRendered(false);
			onClose?.();
		}, transitionMs);
	}, [clearCloseTimeout, dialogRef, onClose, transitionMs, unlockBodyScroll]);

	useEffect(() => {
		const node = dialogRef.current;
		if (!node) return;
		node.setAttribute('inert', '');

		return () => {
			unlockBodyScroll();
			clearCloseTimeout();
		};
	}, [dialogRef, unlockBodyScroll, clearCloseTimeout]);

	useEffect(() => {
		const node = dialogRef.current;
		if (!node) return;

		const handleCancel = (event: Event) => {
			if (!closeOnEscape) {
				event.preventDefault();
				return;
			}

			event.preventDefault();
			handleClose();
		};

		node.addEventListener('cancel', handleCancel);

		return () => {
			node.removeEventListener('cancel', handleCancel);
		};
	}, [dialogRef, handleClose, closeOnEscape]);

	return { isOpen, isRendered, handleOpen, handleClose };
};
