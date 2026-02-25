import type { ReactNode } from 'react';
import { Button } from '@/components/shared/Button';
import { DialogModal, type DialogModalSize } from '@/components/shared/DialogModal';
import type { ModalController } from '@/hooks/useModal';

interface InfoModalProps {
	controller: ModalController;
	title: string;
	children?: ReactNode;
	closeLabel?: string;
	buttonLabel?: string;
	size?: DialogModalSize;
	closeOnBackdrop?: boolean;
	ariaLabel?: string;
}

export const InfoModal = ({
	controller,
	title,
	children,
	closeLabel = 'Close dialog',
	buttonLabel = 'Close',
	size = 'md',
	closeOnBackdrop = true,
	ariaLabel,
}: InfoModalProps) => {
	if (!controller.isRendered) return null;

	return (
		<DialogModal
			{...controller.dialogProps}
			size={size}
			closeOnBackdrop={closeOnBackdrop}
			ariaLabel={ariaLabel}
			closeLabel={closeLabel}
		>
			<DialogModal.Header>
				<DialogModal.Title>{title}</DialogModal.Title>
			</DialogModal.Header>
			<DialogModal.Body>{children}</DialogModal.Body>
			<DialogModal.Footer>
				<Button variant="secondary" onClick={controller.close}>
					{buttonLabel}
				</Button>
			</DialogModal.Footer>
		</DialogModal>
	);
};
