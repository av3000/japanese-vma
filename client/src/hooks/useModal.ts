import type { RefObject } from 'react';
import { useCallback, useEffect, useId, useMemo, useRef, useState } from 'react';
import { useOnClickAway } from '@/hooks/useOnClickAway';
import { useScrollLock } from '@/hooks/useScrollLock';

export interface UseModalOptions {
	id?: string;
	onOpen?: () => void;
	onClose?: () => void;
	transitionMs?: number;
	closeOnEscape?: boolean;
	lockScroll?: boolean;
}

export interface ModalDialogProps {
	id: string;
	dialogRef: RefObject<HTMLDialogElement | null>;
	isOpen: boolean;
	onClose: () => void;
}

export interface ModalController {
	id: string;
	dialogRef: RefObject<HTMLDialogElement | null>;
	isOpen: boolean;
	isRendered: boolean;
	open: () => void;
	close: () => void;
	dialogProps: ModalDialogProps;
}

export const useModal = (
	dialogRef: RefObject<HTMLDialogElement | null>,
	{ id: idProp, onOpen, onClose, transitionMs = 400, closeOnEscape = true, lockScroll = true }: UseModalOptions = {},
): ModalController => {
	const reactId = useId();
	const id = useMemo(() => idProp ?? `dialog-${reactId}`, [idProp, reactId]);
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
		const target = activeElementRef.current;
		activeElementRef.current = null;
		if (target?.focus) {
			target.focus();
		}
	}, []);

	const open = useCallback(() => {
		clearOpenTimeout();
		clearCloseTimeout();
		setIsRendered(true);

		if (dialogRef.current?.open) return;

		activeElementRef.current = document.activeElement instanceof HTMLElement ? document.activeElement : null;

		openTimeoutRef.current = window.setTimeout(() => {
			const node = dialogRef.current;
			if (!node || node.open) return;
			setIsOpen(true);
			node.removeAttribute('inert');
			node.showModal();
			if (lockScroll) {
				setScrollLock(true);
			}
			onOpen?.();
		}, 10);
	}, [clearOpenTimeout, clearCloseTimeout, dialogRef, lockScroll, onOpen, setScrollLock]);

	const close = useCallback(() => {
		clearOpenTimeout();
		clearCloseTimeout();
		setIsOpen(false);
		if (dialogRef.current?.open) {
			dialogRef.current.close();
			dialogRef.current.setAttribute('inert', '');
			if (lockScroll) {
				setScrollLock(false);
			}
		}

		closeTimeoutRef.current = window.setTimeout(() => {
			setIsRendered(false);
			onClose?.();
			restoreFocus();
		}, transitionMs);
	}, [
		clearOpenTimeout,
		clearCloseTimeout,
		dialogRef,
		lockScroll,
		onClose,
		restoreFocus,
		setScrollLock,
		transitionMs,
	]);

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

	// TODO: is this really must needed?
	const dialogProps = useMemo(
		() => ({
			id,
			dialogRef,
			isOpen,
			onClose: close,
		}),
		[id, isOpen, close],
	);

	return { id, dialogRef, isOpen, isRendered, open, close, dialogProps };
};
