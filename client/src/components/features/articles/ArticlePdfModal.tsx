import { Button } from '@/components/shared/Button';
import { DialogModal, type DialogModalSize } from '@/components/shared/DialogModal';
import { Icon } from '@/components/shared/Icon';
import type { ModalController } from '@/hooks/useModal';

interface ArticlePdfModalProps {
	controller: ModalController;
	onDownload: (type: 'kanji' | 'words') => void;
	isDownloadEnabled: boolean;
	title?: string;
	ariaLabel?: string;
	size?: DialogModalSize;
}

export const ArticlePdfModal = ({
	controller,
	onDownload,
	isDownloadEnabled,
	title = 'Generate PDF',
	ariaLabel = 'Generate PDF',
	size = 'sm',
}: ArticlePdfModalProps) => {
	if (!controller.isRendered) return null;

	return (
		<DialogModal {...controller.dialogProps} size={size} ariaLabel={ariaLabel}>
			<div className="text-center p-4">
				<h5 className="mb-4">{title}</h5>
				<Button
					variant="ghost"
					className="w-100 mb-2 border"
					disabled={!isDownloadEnabled}
					onClick={() => onDownload('kanji')}
				>
					Kanji List <Icon size="sm" name="filePdfSolid" className="ml-2" />
				</Button>
				<Button variant="ghost" className="w-100 border" onClick={() => onDownload('words')}>
					Vocabulary List <Icon size="sm" name="filePdfSolid" className="ml-2" />
				</Button>
			</div>
		</DialogModal>
	);
};
