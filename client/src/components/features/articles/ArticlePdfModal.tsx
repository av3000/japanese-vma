import { Button } from '@/components/shared/Button';
import { DialogModal, type DialogModalSize } from '@/components/shared/DialogModal';
import { Icon } from '@/components/shared/Icon';
import { Stack } from '@/components/shared/layout';
import type { ModalController } from '@/hooks/useModal';
import styles from './ArticlePdfModal.module.css';

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
		<DialogModal
			id={controller.id}
			dialogRef={controller.dialogRef}
			isOpen={controller.isOpen}
			onClose={controller.close}
			size={size}
			ariaLabel={ariaLabel}
		>
			<Stack gap="lg" className={styles.body}>
				<h5 className={styles.title}>{title}</h5>
				<Stack gap="xs">
					<Button
						variant="ghost"
						isFullWidth
						className={styles.action}
						disabled={!isDownloadEnabled}
						onClick={() => onDownload('kanji')}
					>
						Kanji List <Icon size="sm" name="filePdfSolid" className={styles.actionIcon} />
					</Button>
					<Button variant="ghost" isFullWidth className={styles.action} onClick={() => onDownload('words')}>
						Vocabulary List <Icon size="sm" name="filePdfSolid" className={styles.actionIcon} />
					</Button>
				</Stack>
			</Stack>
		</DialogModal>
	);
};
