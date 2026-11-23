import { useCallback, useRef, useState, type RefObject } from 'react';
import type { UseMutationResult } from '@tanstack/react-query';
import { useModal, type ModalController } from '@/hooks/useModal';

interface UseDashboardDeleteOptions {
	/** A delete mutation keyed by uuid, e.g. `useDeleteArticleMutation()`. */
	mutation: Pick<UseMutationResult<unknown, unknown, string>, 'mutate' | 'isPending'>;
	/** The confirmation dialog's id, unique per tab. */
	modalId: string;
}

export interface DashboardDelete<Row> {
	/** The count line; it takes focus after a delete, since the row that had focus is gone. */
	summaryRef: RefObject<HTMLParagraphElement | null>;
	deleteModal: ModalController;
	/** The row the open dialog asks about. */
	target: Row | null;
	/** True after a failed delete, until the next one is requested. */
	deleteFailed: boolean;
	isDeleting: boolean;
	/** For the table's `onDelete`: remember the row and ask first. Stable, so rows do not re-render. */
	requestDelete: (row: Row) => void;
	/** For the dialog's confirm button. */
	confirmDelete: () => void;
}

/**
 * The dashboard's confirm-then-delete flow, shared by the Articles and Lists tabs: one dialog per
 * tab holding the target row, the mutation, focus on the count line after success, and a plain
 * failure flag for the page to show.
 */
export const useDashboardDelete = <Row extends { uuid: string }>({
	mutation,
	modalId,
}: UseDashboardDeleteOptions): DashboardDelete<Row> => {
	const summaryRef = useRef<HTMLParagraphElement>(null);
	const dialogRef = useRef<HTMLDialogElement | null>(null);
	const deleteModal = useModal(dialogRef, { id: modalId });
	const [target, setTarget] = useState<Row | null>(null);
	const [deleteFailed, setDeleteFailed] = useState(false);
	const { open, close } = deleteModal;

	const requestDelete = useCallback(
		(row: Row) => {
			setDeleteFailed(false);
			setTarget(row);
			open();
		},
		[open],
	);

	const confirmDelete = () => {
		if (!target) return;

		mutation.mutate(target.uuid, {
			onSuccess: () => {
				close();
				summaryRef.current?.focus();
			},
			onError: () => {
				close();
				setDeleteFailed(true);
			},
		});
	};

	return {
		summaryRef,
		deleteModal,
		target,
		deleteFailed,
		isDeleting: mutation.isPending,
		requestDelete,
		confirmDelete,
	};
};
