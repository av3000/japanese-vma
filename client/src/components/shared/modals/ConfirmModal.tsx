import type { ReactNode } from 'react';
import { Button } from '@/components/shared/Button';
import type { ButtonVariant } from '@/components/shared/Button/types';
import { DialogModal, type DialogModalSize } from '@/components/shared/DialogModal';
import type { ModalController } from '@/hooks/useModal';

interface ConfirmModalProps {
	controller: ModalController;
	title: string;
	children?: ReactNode;
	confirmLabel?: string;
	/** Visual variant of the confirm button. Use `danger` for destructive confirmations. */
	confirmVariant?: ButtonVariant;
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
	confirmVariant = 'primary',
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
				<Button variant={confirmVariant} onClick={onConfirm} disabled={isConfirmLoading}>
					{isConfirmLoading ? 'Working...' : confirmLabel}
				</Button>
			</DialogModal.Footer>
		</DialogModal>
	);
};
