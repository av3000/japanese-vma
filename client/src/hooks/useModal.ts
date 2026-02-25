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

export const useModal = ({
	id: idProp,
	onOpen,
	onClose,
	transitionMs = 400,
	closeOnEscape = true,
	lockScroll = true,
}: UseModalOptions = {}): ModalController => {
	const reactId = useId();
	const id = useMemo(() => idProp ?? `dialog-${reactId}`, [idProp, reactId]);
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const [isOpen, setIsOpen] = useState(false);
	const [isRendered, setIsRendered] = useState(false);
	const closeTimeoutRef = useRef<number | null>(null);
	const activeElementRef = useRef<HTMLElement | null>(null);
	const setScrollLock = useScrollLock();

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
		clearCloseTimeout();
		setIsRendered(true);

		if (dialogRef.current?.open) return;

		activeElementRef.current = document.activeElement instanceof HTMLElement ? document.activeElement : null;

		setTimeout(() => {
			const node = dialogRef.current;
			if (!node || node.open) return;
			setIsOpen(true);
			node.removeAttribute('inert');
			node.showModal();
			onOpen?.();
		}, 10);
	}, [clearCloseTimeout, onOpen]);

	const close = useCallback(() => {
		clearCloseTimeout();
		setIsOpen(false);

		if (dialogRef.current?.open) {
			dialogRef.current.close();
			dialogRef.current.setAttribute('inert', '');
		}

		closeTimeoutRef.current = window.setTimeout(() => {
			setIsRendered(false);
			onClose?.();
			restoreFocus();
		}, transitionMs);
	}, [clearCloseTimeout, onClose, restoreFocus, transitionMs]);

	useEffect(() => {
		const node = dialogRef.current;
		if (!node) return;
		node.setAttribute('inert', '');

		return () => {
			clearCloseTimeout();
		};
	}, [clearCloseTimeout]);

	useEffect(() => {
		if (!lockScroll) {
			setScrollLock(false);
			return;
		}

		setScrollLock(isOpen);
	}, [isOpen, lockScroll, setScrollLock]);

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
	}, [close, closeOnEscape]);

	useOnClickAway(dialogRef, close, isOpen);

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
