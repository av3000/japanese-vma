import { Button } from '@/components/shared/Button';
import { DialogModal } from '@/components/shared/DialogModal';
import type { ModalController } from '@/hooks/useModal';

interface ArticleReviewModalProps {
	controller: ModalController;
	status: number;
	onStatusChange: (nextStatus: number) => void;
	onSave: () => void;
	isProcessing: boolean;
	title?: string;
	description?: string;
	cancelLabel?: string;
	saveLabel?: string;
	ariaLabel?: string;
}

export const ArticleReviewModal = ({
	controller,
	status,
	onStatusChange,
	onSave,
	isProcessing,
	title = 'Review Article',
	description = 'Change Visibility/Approval Status',
	cancelLabel = 'Cancel',
	saveLabel = 'Save Changes',
	ariaLabel = 'Review Article',
}: ArticleReviewModalProps) => {
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
				<p>{description}</p>
				<select
					className="form-control"
					value={status}
					onChange={(e) => onStatusChange(Number(e.target.value))}
				>
					{/* TODO: should use const values instead */}
					<option value={0}>Pending</option>
					<option value={1}>Review</option>
					<option value={2}>Reject</option>
					<option value={3}>Approve</option>
				</select>
			</DialogModal.Body>
			<DialogModal.Footer>
				<Button variant="secondary" onClick={controller.close}>
					{cancelLabel}
				</Button>
				<Button variant="success" onClick={onSave} disabled={isProcessing}>
					{isProcessing ? 'Saving...' : saveLabel}
				</Button>
			</DialogModal.Footer>
		</DialogModal>
	);
};
