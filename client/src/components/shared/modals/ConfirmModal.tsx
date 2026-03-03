import type { ReactNode } from 'react';
import { Button } from '@/components/shared/Button';
import { DialogModal, type DialogModalSize } from '@/components/shared/DialogModal';
import type { ModalController } from '@/hooks/useModal';

interface ConfirmModalProps {
	controller: ModalController;
	title: string;
	children?: ReactNode;
	confirmLabel?: string;
	cancelLabel?: string;
	onConfirm: () => void;
	onCancel?: () => void;
	isConfirmLoading?: boolean;
	size?: DialogModalSize;
	closeOnBackdrop?: boolean;
	ariaLabel?: string;
}

export const ConfirmModal = ({
	controller,
	title,
	children,
	confirmLabel = 'Confirm',
	cancelLabel = 'Cancel',
	onConfirm,
	onCancel,
	isConfirmLoading = false,
	size = 'md',
	closeOnBackdrop = true,
	ariaLabel,
}: ConfirmModalProps) => {
	if (!controller.isRendered) return null;

	const handleCancel = () => {
		onCancel?.();
		controller.close();
	};

	return (
		<DialogModal
			id={controller.id}
			dialogRef={controller.dialogRef}
			isOpen={controller.isOpen}
			onClose={controller.close}
			size={size}
			closeOnBackdrop={closeOnBackdrop}
			ariaLabel={ariaLabel}
		>
			<DialogModal.Header>
				<DialogModal.Title>{title}</DialogModal.Title>
			</DialogModal.Header>
			<DialogModal.Body>{children}</DialogModal.Body>
			<DialogModal.Footer>
				<Button variant="secondary" onClick={handleCancel}>
					{cancelLabel}
				</Button>
				<Button variant="primary" onClick={onConfirm} disabled={isConfirmLoading}>
					{isConfirmLoading ? 'Working...' : confirmLabel}
				</Button>
			</DialogModal.Footer>
		</DialogModal>
	);
};
