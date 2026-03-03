import { Button } from '@/components/shared/Button';
import { DialogModal } from '@/components/shared/DialogModal';
import type { ModalController } from '@/hooks/useModal';

interface DeleteInstanceModalProps {
	controller: ModalController;
	instanceName: string;
	onDelete: () => void;
	isProcessing: boolean;
	title?: string;
	cancelLabel?: string;
	deleteLabel?: string;
	ariaLabel?: string;
}

export const DeleteInstanceModal = ({
	controller,
	instanceName,
	onDelete,
	isProcessing,
	title = 'Are you absolutely sure?',
	cancelLabel = 'Cancel',
	deleteLabel = 'Yes, Delete Instance',
	ariaLabel = 'Delete instance',
}: DeleteInstanceModalProps) => {
	if (!controller.isRendered) return null;

	return (
		<DialogModal
			id={controller.id}
			dialogRef={controller.dialogRef}
			isOpen={controller.isOpen}
			onClose={controller.close}
			ariaLabel={ariaLabel}
		>
			<DialogModal.Header>
				<DialogModal.Title>{title}</DialogModal.Title>
			</DialogModal.Header>
			<DialogModal.Body>
				{/* TODO: Think about idea of having the archive of articles, or atleast trashbin which would save articles for a month, etc.. */}
				This action cannot be undone. This will permanently delete <strong>{instanceName}</strong>.
			</DialogModal.Body>
			<DialogModal.Footer>
				<Button variant="secondary" onClick={controller.close}>
					{cancelLabel}
				</Button>
				<Button variant="danger" onClick={onDelete} disabled={isProcessing}>
					{isProcessing ? 'Deleting...' : deleteLabel}
				</Button>
			</DialogModal.Footer>
		</DialogModal>
	);
};
